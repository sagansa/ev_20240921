<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Models\UserSubscription;
use Exception;
use Firebase\JWT\JWT;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Monetization / entitlement server-side.
 *
 * Prinsip: Pro terikat ke AKUN APP, bukan store account. Tabel
 * `user_subscriptions` menyimpan entitlement per (user_id, provider,
 * original_transaction_id). Ganti akun → config endpoint membaca entitlement
 * akun baru (yang tidak membeli → non-Pro). Purchase hanya diregistrasi oleh
 * akun yang membeli (klien hanya memanggil register saat buy flow sukses).
 *
 * Verifikasi server-side (opsional, via kredensial env):
 * - apple: App Store Server API (GET /inApps/v1/transactions/{id}).
 * - google: Google Play Developer API.
 * Bila kredensial tidak dikonfigurasi, klaim client dari StoreKit/Play Billing
 * yang sudah diverifikasi lokal (`.verified`) dipercaya (mode dev).
 */
class MonetizationController extends Controller
{
    /** Plan yang disajikan paywall — mirror fallback di shared KMP. */
    private const PLANS = [
        [
            'sku' => 'remove_ads_monthly',
            'title' => 'Bebas Iklan Bulanan',
            'subtitle' => 'Hilangkan seluruh banner iklan AdMob selama 1 Bulan penuh',
            'price_idr' => 3000,
            'price_formatted' => 'Rp 3.000 / bln',
            'credits' => 0,
            'ad_free_days' => 30,
            'badge_text' => 'POPULER',
            'is_best_value' => false,
        ],
        [
            'sku' => 'remove_ads_yearly',
            'title' => 'Bebas Iklan Tahunan',
            'subtitle' => 'Hilangkan seluruh banner iklan AdMob selama 1 Tahun penuh',
            'price_idr' => 30000,
            'price_formatted' => 'Rp 30.000 / thn',
            'credits' => 0,
            'ad_free_days' => 365,
            'badge_text' => 'HEMAT 17%',
            'is_best_value' => true,
        ],
    ];

    private const PRODUCTS = ['remove_ads_monthly', 'remove_ads_yearly', 'remove_ads_lifetime'];

    /**
     * GET /api/v1/monetization/config — entitlement + plans untuk user
     * yang sedang login. Juga dipakai saat foreground / ganti akun.
     */
    public function config(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'free_initial_credits' => 5,
                'plans' => self::PLANS,
                ...$this->entitlement($request->user()),
            ],
        ]);
    }

    /**
     * POST /api/v1/monetization/register-apple — daftarkan transaksi App Store
     * hasil purchase akun yang login. Idempotent per original_transaction_id.
     */
    public function registerApple(Request $request): JsonResponse
    {
        $request->validate([
            'original_transaction_id' => 'required|string',
            'transaction_id' => 'nullable|string',
            'product_id' => 'required|in:'.implode(',', self::PRODUCTS),
            'expires_at' => 'nullable|integer',          // ms epoch
            'revocation_date' => 'nullable|integer',     // ms epoch → revoked
            'auto_renewing' => 'nullable|boolean',
        ]);

        $verified = $this->verifyAppleTransaction($request->string('transaction_id')->toString());

        $revoked = $request->filled('revocation_date')
            || ($verified !== null && isset($verified['revocation_date']));

        if ($verified !== null) {
            $expiresMs = $verified['expires_date'] ?? $request->integer('expires_at', 0) ?: null;
            $autoRenewing = (bool) ($verified['auto_renewing'] ?? $request->boolean('auto_renewing'));
            $productId = $verified['product_id'] ?? $request->string('product_id')->toString();
            if (! in_array($productId, self::PRODUCTS, true)) {
                $productId = $request->string('product_id')->toString();
            }
            $originalId = $verified['original_transaction_id'] ?? $request->string('original_transaction_id')->toString();
            $transactionId = $verified['transaction_id'] ?? $request->string('transaction_id')->toString() ?: null;
            $storeTransactionId = $transactionId;
        } else {
            $expiresMs = $request->integer('expires_at', 0) ?: null;
            $autoRenewing = $request->boolean('auto_renewing');
            $productId = $request->string('product_id')->toString();
            $originalId = $request->string('original_transaction_id')->toString();
            $storeTransactionId = $request->string('transaction_id')->toString() ?: null;
        }

        if ($request->filled('revocation_date')) {
            $expiresMs = null;
        }

        $subscription = $request->user()->userSubscriptions()->updateOrCreate(
            [
                'provider' => 'apple',
                'original_transaction_id' => $originalId,
            ],
            [
                'product_id' => $productId,
                'store_transaction_id' => $storeTransactionId,
                'status' => $revoked ? 'revoked' : 'active',
                'expires_at' => $expiresMs ? now()->createFromTimestampMs($expiresMs) : null,
                'auto_renewing' => $autoRenewing,
                'raw' => $verified !== null ? $verified : null,
            ]
        );

        Log::info('apple_subscription_registered', [
            'user_id' => $request->user()->id,
            'original_transaction_id' => $originalId,
            'status' => $subscription->status,
            'expires_at' => optional($subscription->expires_at)->toIso8601String(),
            'server_verified' => $verified !== null,
        ]);

        return response()->json([
            'success' => true,
            'message' => $revoked ? 'Langganan dicabut' : 'Langganan terdaftar',
            'data' => $this->entitlement($request->user()),
        ]);
    }

    /**
     * POST /api/v1/monetization/register-google — daftarkan purchase Google
     * Play (subscription) untuk akun yang login.
     */
    public function registerGoogle(Request $request): JsonResponse
    {
        $request->validate([
            'purchase_token' => 'required|string',
            'product_id' => 'required|in:'.implode(',', self::PRODUCTS),
            'package_name' => 'nullable|string',
            'expires_at' => 'nullable|integer',          // ms epoch
            'auto_renewing' => 'nullable|boolean',
        ]);

        $package = $request->string('package_name')->toString()
            ?: config('services.google_play.package_name');

        $verified = $this->verifyGooglePurchase($package, $request->string('product_id')->toString(), $request->string('purchase_token')->toString());

        $expiresMs = $verified['expiry_time_millis'] ?? $request->integer('expires_at', 0) ?: null;
        $autoRenewing = (bool) ($verified['auto_renewing'] ?? $request->boolean('auto_renewing'));

        $subscription = $request->user()->userSubscriptions()->updateOrCreate(
            [
                'provider' => 'google',
                'original_transaction_id' => $request->string('purchase_token')->toString(),
            ],
            [
                'product_id' => $request->string('product_id')->toString(),
                'store_transaction_id' => $request->string('purchase_token')->toString(),
                'status' => 'active',
                'expires_at' => $expiresMs ? now()->createFromTimestampMs($expiresMs) : null,
                'auto_renewing' => $autoRenewing,
                'raw' => $verified !== null ? $verified : null,
            ]
        );

        Log::info('google_subscription_registered', [
            'user_id' => $request->user()->id,
            'purchase_token' => $request->string('purchase_token')->toString(),
            'status' => $subscription->status,
            'expires_at' => optional($subscription->expires_at)->toIso8601String(),
            'server_verified' => $verified !== null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Langganan terdaftar',
            'data' => $this->entitlement($request->user()),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Entitlement aktif untuk user: status bebas-iklan + expiry tertinggi.
     */
    private function entitlement(User $user): array
    {
        $active = $user->userSubscriptions()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->get();

        $maxExpires = $active->map(fn ($s) => $s->expires_at)->filter()->max();

        return [
            'is_ad_free_active' => $active->isNotEmpty(),
            'ad_free_until_timestamp' => $maxExpires ? $maxExpires->valueOf() : 0,
        ];
    }

    /**
     * Verifikasi transaksi App Store via App Store Server API.
     *
     * @return array|null claims transaksi bila terverifikasi server-side;
     *                    null bila kredensial tidak dikonfigurasi (percya client)
     *                    — bedakan dgn kegagalan verifikasi yg melempar Exception.
     */
    private function verifyAppleTransaction(?string $transactionId): ?array
    {
        if (! $transactionId) {
            return null;
        }

        $jwt = $this->appleStoreApiJwt();
        if (! $jwt) {
            return null; // kredensial tidak ada → dev mode, percya client
        }

        $base = config('services.apple.store_api_sandbox', true)
            ? 'https://api.storekit-sandbox.itunes.apple.com'
            : 'https://api.storekit.itunes.apple.com';

        $response = Http::withToken($jwt, 'Bearer')
            ->get("$base/inApps/v1/transactions/$transactionId");

        if (! $response->ok() || ! is_array($response->json('data'))) {
            throw new Exception('App Store Server API verification failed: HTTP '.$response->status());
        }

        $signedTransaction = $response->json('data.signedTransaction');
        if (! is_string($signedTransaction)) {
            throw new Exception('App Store Server API returned no signed transaction');
        }

        return $this->decodeJwsPayload($signedTransaction);
    }

    /** JWT (ES256) untuk App Store Server API. */
    private function appleStoreApiJwt(): ?string
    {
        $team = config('services.apple.team_id');
        $keyId = config('services.apple.key_id');
        $path = config('services.apple.private_key_path');
        $bundleId = config('services.apple.bundle_id');

        if (! $team || ! $keyId || ! $path || ! is_file($path) || ! $bundleId) {
            return null;
        }

        $privateKey = openssl_pkey_get_private((string) file_get_contents($path));
        if (! $privateKey) {
            return null;
        }

        return JWT::encode(
            [
                'iss' => $team,
                'iat' => now()->timestamp,
                'exp' => now()->addMinutes(20)->timestamp,
                'aud' => 'appstoreconnect-v1',
                'bid' => $bundleId,
            ],
            $privateKey,
            'ES256',
            $keyId
        );
    }

    /**
     * Verifikasi purchase Google Play via Play Developer API.
     *
     * @return array|null resource purchase bila terverifikasi; null bila
     *                    service account tidak dikonfigurasi.
     */
    private function verifyGooglePurchase(string $package, string $productId, string $purchaseToken): ?array
    {
        $saPath = config('services.google_play.service_account_json');
        if (! $package || ! $saPath || ! is_file($saPath)) {
            return null;
        }

        $accessToken = $this->googleAccessToken($saPath);
        $endpoint = "https://androidpublisher.googleapis.com/androidpublisher/v3/applications/$package/purchases/subscriptions/$productId/tokens/$purchaseToken";

        $response = Http::withToken($accessToken, 'Bearer')->get($endpoint);

        if (! $response->ok()) {
            throw new Exception('Google Play Developer API verification failed: HTTP '.$response->status());
        }

        return $response->json();
    }

    /** Access token via service account JWT (RS256) → oauth2 token endpoint. */
    private function googleAccessToken(string $saPath): string
    {
        $sa = json_decode((string) file_get_contents($saPath), true);
        if (! is_array($sa) || empty($sa['client_email']) || empty($sa['private_key'])) {
            throw new Exception('Invalid Google Play service account JSON');
        }

        $now = now()->timestamp;
        $jwt = JWT::encode(
            [
                'iss' => $sa['client_email'],
                'scope' => 'https://www.googleapis.com/auth/androidpublisher',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ],
            $sa['private_key'],
            'RS256'
        );

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (! $response->ok() || ! $response->json('access_token')) {
            throw new Exception('Google OAuth token exchange failed: HTTP '.$response->status());
        }

        return $response->json('access_token');
    }

    /** Decode payload JWS (tanpa verify ulang — sudah via HTTPS dari Apple). */
    private function decodeJwsPayload(string $jws): array
    {
        $parts = explode('.', $jws);
        if (count($parts) < 2) {
            throw new Exception('Malformed JWS');
        }
        $payload = json_decode($this->base64UrlDecode($parts[1]), true);
        if (! is_array($payload)) {
            throw new Exception('Malformed JWS payload');
        }

        return $payload;
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/'), true) ?: '';
    }
}