<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Firebase\JWT\Key;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    public function googleLogin(Request $request): JsonResponse
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        $payload = $this->verifyGoogleToken($request->id_token);
        if (! $payload) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Google ID token',
            ], 401);
        }

        $googleId = $payload['sub'];
        $email = $payload['email'] ?? null;
        $name = $payload['name'] ?? 'Google User';
        $avatar = $payload['picture'] ?? null;

        $user = User::where('google_id', $googleId)->first();

        if (! $user && $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->update(['google_id' => $googleId]);
            }
        }

        if (! $user) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'google_id' => $googleId,
                'avatar' => $avatar,
                'password' => Hash::make(Str::random(32)),
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Google login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar ?? $user->profile_photo_url,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    public function appleLogin(Request $request): JsonResponse
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        $payload = $this->verifyAppleToken($request->id_token);
        if (! $payload) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Apple ID token',
            ], 401);
        }

        $appleId = $payload['sub'];
        $email = $payload['email'] ?? null;
        $name = $payload['name'] ?? 'Apple User';

        $user = User::where('apple_id', $appleId)->first();

        if (! $user && $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->update(['apple_id' => $appleId]);
            }
        }

        if (! $user) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'apple_id' => $appleId,
                'password' => Hash::make(Str::random(32)),
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Apple login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar ?? $user->profile_photo_url,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'authenticated' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar ?? $user->profile_photo_url,
                    'has_google' => ! is_null($user->google_id),
                    'has_apple' => ! is_null($user->apple_id),
                ],
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    private function verifyGoogleToken(string $idToken): ?array
    {
        // Accept tokens whose audience (aud) matches ANY of the configured Google OAuth
        // client IDs. Mobile clients (Android/iOS) issue tokens with their own per-platform
        // client ID as the audience; the legacy GoogleSignInOptions flow uses the Web client
        // ID. Accepting all of them avoids audience-mismatch rejections across platforms.
        $allowedAudiences = array_values(array_filter([
            config('services.google.client_id'),       // Web client ID (server-side)
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

    private function verifyAppleToken(string $idToken): ?array
    {
        $clientId = config('services.apple.service_id');
        if (! $clientId) {
            return null;
        }

        try {
            $keySet = $this->fetchJwkKeySet('https://appleid.apple.com/auth/keys');
            $payload = JWT::decode($idToken, $keySet);
            $payloadArr = (array) $payload;

            if (($payloadArr['aud'] ?? null) !== $clientId) {
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
     * Fetch a JWK set from the given URL and parse it into a key set usable by JWT::decode.
     * Uses Firebase\JWT\JWK (already a project dependency) rather than hand-rolling DER/PEM.
     */
    private function fetchJwkKeySet(string $jwksUrl): array
    {
        $jwks = Http::get($jwksUrl)->json();
        if (! is_array($jwks) || empty($jwks['keys'])) {
            throw new Exception('Invalid or empty JWK set');
        }

        return JWK::parseKeySet($jwks);
    }
}
