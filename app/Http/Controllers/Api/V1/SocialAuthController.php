<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\AppUser;
use App\Models\User;
use App\Services\SocialTokenVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    public function __construct(
        private SocialTokenVerifier $verifier,
    ) {}

    public function googleLogin(Request $request): JsonResponse
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        $payload = $this->verifier->verifyGoogleToken($request->id_token);
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

        $platform = $this->verifier->resolvePlatform('google', $payload);
        AppUser::recordLogin($user->id, 'google', $platform);

        $token = $user->createToken($platform ? "ev-{$platform}" : 'ev')->plainTextToken;

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

        $payload = $this->verifier->verifyAppleToken($request->id_token);
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

        $platform = $this->verifier->resolvePlatform('apple', $payload);
        AppUser::recordLogin($user->id, 'apple', $platform);

        $token = $user->createToken($platform ? "ev-{$platform}" : 'ev')->plainTextToken;

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
}
