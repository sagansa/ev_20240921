<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
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

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Login an existing user
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid login credentials',
            ], 401);
        }

        $user = Auth::user();

        // Check if email is verified
        // if (!$user->hasVerifiedEmail()) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Please verify your email address',
        //     ], 401);
        // }

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
     * Verify user's email
     */
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
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

        $user->sendEmailVerificationNotification();

        return response()->json([
            'success' => true,
            'message' => 'Verification email sent',
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
}
