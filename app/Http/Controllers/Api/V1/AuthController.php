<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Controller;
use App\Mail\EmailVerificationOtpMail;
use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    /**
     * Masa berlaku kode OTP di cache (menit).
     */
    private const OTP_EXPIRES_MINUTES = 10;

    /**
     * Interval minimum antar pengiriman ulang kode (detik).
     */
    private const OTP_RESEND_SECONDS = 60;

    /**
     * Register a new user
     *
     * User belum bisa login sebelum email diverifikasi via OTP (confirmVerification).
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Ensure default user role exists then assign
        $defaultRole = Role::firstOrCreate(
            ['name' => 'user', 'guard_name' => 'web']
        );
        $user->syncRoles([$defaultRole->name]);

        $this->sendVerificationOtp($user);

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully. Please verify your email.',
            'data' => [
                'user' => $user,
            ],
        ], 201);
    }

    /**
     * Login an existing user
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid login credentials',
            ], 401);
        }

        $user = Auth::user();

        // Check if email is verified
        if (! $user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your email address',
                'data' => [
                    'needs_verification' => true,
                    'email' => $user->email,
                ],
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Trigger pengiriman kode OTP verifikasi email (6 digit) ke email user.
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'message' => 'Email already verified',
            ]);
        }

        return $this->sendVerificationOtp($user);
    }

    /**
     * Konfirmasi kode OTP verifikasi email. Berhasil → tandai email_verified_at.
     */
    public function confirmVerification(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'message' => 'Email sudah terverifikasi.',
            ]);
        }

        $cachedOtp = Cache::get("verify:{$request->email}");

        if (! $cachedOtp || ! hash_equals((string) $cachedOtp, $request->otp)) {
            return response()->json([
                'success' => false,
                'message' => 'Kode verifikasi salah atau sudah kedaluwarsa.',
            ], 422);
        }

        $user->markEmailAsVerified();

        Cache::forget("verify:{$request->email}");

        return response()->json([
            'success' => true,
            'message' => 'Email berhasil diverifikasi. Silakan masuk.',
        ]);
    }

    /**
     * Kirim kode OTP reset password. Selalu return 200 generic agar tidak
     * membocorkan email mana yang terdaftar (anti user-enumeration).
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'success' => true,
                'message' => 'Jika email terdaftar, kode reset telah dikirim.',
            ]);
        }

        $rateLimitKey = "reset:sent:{$request->email}";
        if (Cache::has($rateLimitKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Mohon tunggu 1 menit sebelum mengirim ulang kode.',
            ], 429);
        }

        $otp = $this->generateOtp();
        Cache::put("reset:{$request->email}", $otp, now()->addMinutes(self::OTP_EXPIRES_MINUTES));
        Cache::put($rateLimitKey, true, now()->addSeconds(self::OTP_RESEND_SECONDS));

        Mail::to($user->email)->send(new PasswordResetOtpMail(
            name: $user->name,
            otp: $otp,
            expiresInMinutes: self::OTP_EXPIRES_MINUTES,
        ));

        return response()->json([
            'success' => true,
            'message' => 'Jika email terdaftar, kode reset telah dikirim.',
        ]);
    }

    /**
     * Validasi kode OTP reset + set password baru.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $cachedOtp = Cache::get("reset:{$request->email}");

        if (! $cachedOtp || ! hash_equals((string) $cachedOtp, $request->otp)) {
            return response()->json([
                'success' => false,
                'message' => 'Kode reset salah atau sudah kedaluwarsa.',
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        Cache::forget("reset:{$request->email}");
        Cache::forget("reset:sent:{$request->email}");

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil direset. Silakan masuk.',
        ]);
    }

    /**
     * Refresh the authentication token
     */
    public function refreshToken(Request $request)
    {
        // This method would typically be handled by Laravel Sanctum
        // by creating a new token and invalidating the old one
        $user = $request->user();

        // Revoke all tokens
        $user->tokens()->delete();

        // Create a new token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Logout the user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Generate & simpan kode OTP verifikasi email, lalu kirim via email.
     * Rate-limited 1x per [OTP_RESEND_SECONDS] per alamat email.
     */
    private function sendVerificationOtp(User $user): JsonResponse
    {
        $rateLimitKey = "verify:sent:{$user->email}";
        if (Cache::has($rateLimitKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Mohon tunggu 1 menit sebelum mengirim ulang kode.',
            ], 429);
        }

        $otp = $this->generateOtp();
        Cache::put("verify:{$user->email}", $otp, now()->addMinutes(self::OTP_EXPIRES_MINUTES));
        Cache::put($rateLimitKey, true, now()->addSeconds(self::OTP_RESEND_SECONDS));

        Mail::to($user->email)->send(new EmailVerificationOtpMail(
            name: $user->name,
            otp: $otp,
            expiresInMinutes: self::OTP_EXPIRES_MINUTES,
        ));

        return response()->json([
            'success' => true,
            'message' => 'Kode verifikasi terkirim ke email Anda.',
        ]);
    }

    /**
     * Kode OTP 6 digit.
     */
    private function generateOtp(): string
    {
        return (string) random_int(100000, 999999);
    }
}
