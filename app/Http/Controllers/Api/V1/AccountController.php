<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    /**
     * Soft-delete the authenticated user's account.
     *
     * Security/privacy: data relasional (charges, vehicles, battery, dsb.) tetap
     * tersimpan untuk keutuhan data, tetapi kolom identitas PII di-anonimkan dan
     * semua token Sanctum direvoke sehingga akun tidak dapat dipakai/di-recover.
     * Email sengaja dibuat unik-per-soft-delete agar tidak menabrak unique index.
     */
    public function destroy(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $suffix = $user->id.'_'.now()->timestamp;

        $user->tokens()->delete();

        // Entitlement langganan ikut dihapus — tidak boleh bocor ke akun
        // yang suatu saat memakai email yang sama.
        $user->userSubscriptions()->delete();

        $user->update([
            'name' => 'Pengguna Terhapus',
            'email' => "deleted+{$suffix}@invalid.local",
            'password' => Hash::make(Str::random(64)),
            'google_id' => null,
            'apple_id' => null,
            'avatar' => null,
            'email_verified_at' => null,
        ]);

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Akun berhasil dihapus',
        ]);
    }
}