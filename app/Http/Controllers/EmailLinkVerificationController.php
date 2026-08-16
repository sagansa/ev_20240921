<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Verifikasi email via link sekali-tap — padanan mobile-friendly dari
 * VerificationController bawaan Laravel (email/verify/{id}/{hash}).
 *
 * Perbedaannya: endpoint ini PUBLIK (tanpa session/auth web) karena penerima
 * link adalah user aplikasi mobile yang belum login; keaslian link dijamin
 * middleware `signed` (URL bertanda tangan) + pencocokan hash sha1(email)
 * sesuai konvensi Laravel.
 */
class EmailLinkVerificationController extends Controller
{
    public function verify(Request $request, int $id, string $hash)
    {
        $user = User::find($id);

        // Hash sesuai konvensi Laravel: sha1(email). hash_equals mencegah
        // timing attack pada perbandingan string.
        abort_unless(
            $user !== null && hash_equals(sha1($user->getEmailForVerification()), $hash),
            403,
            'Link verifikasi tidak valid atau sudah rusak.'
        );

        $alreadyVerified = $user->hasVerifiedEmail();

        if (! $alreadyVerified) {
            $user->markEmailAsVerified();
        }

        return view('emails.email-verified', [
            'name' => $user->name,
            'alreadyVerified' => $alreadyVerified,
        ]);
    }
}
