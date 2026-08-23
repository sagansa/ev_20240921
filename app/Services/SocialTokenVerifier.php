<?php

namespace App\Services;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Verifikasi ID token Google & Apple, serta derive platform dari klaim `aud`.
 */
class SocialTokenVerifier
{
    public function verifyGoogleToken(string $idToken): ?array
    {
        $allowedAudiences = array_values(array_filter([
            config('services.google.client_id'),
            config('services.google.android_client_id'),
            config('services.google.ios_client_id'),
        ]));
        if (empty($allowedAudiences)) {
            return null;
        }

        try {
            $keySet = $this->fetchJwkKeySet('https://www.googleapis.com/oauth2/v3/certs');
            $payload = JWT::decode($idToken, $keySet);
            $payloadArr = (array) $payload;

            $aud = $payloadArr['aud'] ?? null;
            if (! in_array($aud, $allowedAudiences, true)) {
                Log::warning('google_id_token_aud_mismatch', ['aud' => $aud, 'allowed' => $allowedAudiences]);
                return null;
            }
            if (! in_array($payloadArr['iss'] ?? null, ['https://accounts.google.com', 'accounts.google.com'], true)) {
                return null;
            }
            if (! isset($payloadArr['sub'])) {
                return null;
            }

            return $payloadArr;
        } catch (Exception) {
            return null;
        }
    }

    public function verifyAppleToken(string $idToken): ?array
    {
        $allowedAudiences = array_values(array_filter([
            config('services.apple.service_id'),
            config('services.apple.bundle_id'),
        ]));
        if (empty($allowedAudiences)) {
            return null;
        }

        try {
            $keySet = $this->fetchJwkKeySet('https://appleid.apple.com/auth/keys');
            $payload = JWT::decode($idToken, $keySet);
            $payloadArr = (array) $payload;

            $aud = $payloadArr['aud'] ?? null;
            if (! in_array($aud, $allowedAudiences, true)) {
                Log::warning('apple_id_token_aud_mismatch', ['aud' => $aud, 'allowed' => $allowedAudiences]);
                return null;
            }
            if (($payloadArr['iss'] ?? null) !== 'https://appleid.apple.com') {
                return null;
            }
            if (! isset($payloadArr['sub'])) {
                return null;
            }

            return $payloadArr;
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Derive platform dari klaim `aud` idToken.
     *
     * Google:
     *   - aud = android_client_id → android
     *   - aud = ios_client_id     → ios
     *   - aud = client_id (web)   → web
     *
     * Apple:
     *   - aud = bundle_id  → ios
     *   - aud = service_id → web
     */
    public function resolvePlatform(string $provider, array $payload): ?string
    {
        $aud = $payload['aud'] ?? null;
        if ($aud === null) {
            return null;
        }

        return match ($provider) {
            'google' => $this->resolveGooglePlatform($aud),
            'apple' => $this->resolveApplePlatform($aud),
            default => null,
        };
    }

    private function resolveGooglePlatform(string $aud): ?string
    {
        if ($aud === config('services.google.android_client_id')) {
            return 'android';
        }
        if ($aud === config('services.google.ios_client_id')) {
            return 'ios';
        }
        if ($aud === config('services.google.client_id')) {
            return 'web';
        }

        return null;
    }

    private function resolveApplePlatform(string $aud): ?string
    {
        if ($aud === config('services.apple.bundle_id')) {
            return 'ios';
        }
        if ($aud === config('services.apple.service_id')) {
            return 'web';
        }

        return null;
    }

    private function fetchJwkKeySet(string $jwksUrl): array
    {
        $jwks = Http::get($jwksUrl)->json();
        if (! is_array($jwks) || empty($jwks['keys'])) {
            throw new Exception('Invalid or empty JWK set');
        }

        return JWK::parseKeySet($jwks);
    }
}
