<?php

namespace Tests\Feature;

use App\Mail\EmailVerificationOtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Feature\Api\ApiTestCase;

/**
 * Verifikasi email via link sekali-tap (gaya Laravel signed URL) —
 * GET /email/verify-link/{id}/{hash} (publik, middleware signed).
 */
class EmailLinkVerificationTest extends ApiTestCase
{
    private function signedUrl(?User $user, ?int $id = null, ?string $hash = null): string
    {
        return URL::temporarySignedRoute('email.verify-link', now()->addHour(), [
            'id' => $id ?? $user->getKey(),
            'hash' => $hash ?? sha1($user->getEmailForVerification()),
        ]);
    }

    private function unverifiedUser(): User
    {
        return User::factory()->create(['email_verified_at' => null]);
    }

    public function test_link_marks_email_verified(): void
    {
        $user = $this->unverifiedUser();

        $this->get($this->signedUrl($user))
            ->assertOk()
            ->assertSee('Berhasil Diverifikasi');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_link_is_idempotent_on_second_visit(): void
    {
        $user = $this->unverifiedUser();

        $this->get($this->signedUrl($user))->assertOk();
        $this->get($this->signedUrl($user))
            ->assertOk()
            ->assertSee('Sudah Terverifikasi');
    }

    public function test_valid_signature_but_wrong_hash_rejected(): void
    {
        $user = $this->unverifiedUser();

        // Hash salah namun signature-nya valid (param ikut ditandatangani) —
        // controller yang harus menolak lewat pencocokan sha1(email).
        $url = $this->signedUrl($user, hash: sha1('bukan-email-user@example.com'));

        try {
            $this->get($url);
            $this->fail('Expected HttpException(403) for mismatched hash.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_unknown_user_id_rejected(): void
    {
        $url = $this->signedUrl(null, id: 999999999, hash: sha1('x@example.com'));

        try {
            $this->get($url);
            $this->fail('Expected HttpException(403) for unknown user.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_register_email_contains_one_tap_link(): void
    {
        Mail::fake();

        $email = uniqid('tester') . '@example.com';

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Tester Link',
            'email' => $email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertStatus(201);

        Mail::assertSent(EmailVerificationOtpMail::class, function (EmailVerificationOtpMail $mail) {
            return $mail->verificationUrl !== null
                && str_contains($mail->verificationUrl, '/email/verify-link/');
        });
    }
}
