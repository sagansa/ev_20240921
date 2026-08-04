<?php

namespace Tests\Feature\Api;

use App\Mail\EmailVerificationOtpMail;
use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthOtpTest extends TestCase
{
    public function test_register_does_not_issue_token_but_sends_otp(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonMissingPath('data.token');

        $this->assertDatabaseHas('users', ['email' => 'budi@example.com']);

        Mail::assertSent(EmailVerificationOtpMail::class, function ($mail) {
            return $mail->hasTo('budi@example.com') && strlen($mail->otp) === 6;
        });
    }

    public function test_login_gated_until_email_verified(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'belum@example.com',
            'password' => Hash::make('password123'),
        ]);
        $this->assertNull($user->email_verified_at);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'belum@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.needs_verification', true)
            ->assertJsonPath('data.email', 'belum@example.com')
            ->assertJsonMissingPath('data.token');
    }

    public function test_verify_email_and_confirm_otp_unlocks_login(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'verif@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Send OTP
        $this->postJson('/api/v1/auth/verify-email', ['email' => 'verif@example.com'])
            ->assertOk();

        $otp = Cache::get('verify:verif@example.com');
        $this->assertNotNull($otp);

        // Wrong OTP → 422
        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => 'verif@example.com',
            'otp' => '000000',
        ])->assertStatus(422);

        // Correct OTP
        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => 'verif@example.com',
            'otp' => $otp,
        ])->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotNull($user->fresh()->email_verified_at);

        // Login now works
        $this->postJson('/api/v1/auth/login', [
            'email' => 'verif@example.com',
            'password' => 'password123',
        ])->assertOk()
            ->assertJsonPath('data.token', fn ($token) => is_string($token) && strlen($token) > 0);
    }

    public function test_forgot_and_reset_password_flow(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => Hash::make('old-password'),
        ]);

        // forgot-password returns generic 200 for known & unknown emails
        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'reset@example.com'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $unknown = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'ghost@example.com']);
        $unknown->assertOk()
            ->assertJsonPath('message', 'Jika email terdaftar, kode reset telah dikirim.');

        Mail::assertSent(PasswordResetOtpMail::class, function ($mail) {
            return $mail->hasTo('reset@example.com');
        });

        $otp = Cache::get('reset:reset@example.com');
        $this->assertNotNull($otp);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'reset@example.com',
            'otp' => $otp,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-password123', $user->fresh()->password));
        $this->assertNull(Cache::get('reset:reset@example.com'));

        // New password works on login
        $this->postJson('/api/v1/auth/login', [
            'email' => 'reset@example.com',
            'password' => 'new-password123',
        ])->assertOk();
    }

    public function test_resend_verification_otp_is_rate_limited(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'limit@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->postJson('/api/v1/auth/verify-email', ['email' => 'limit@example.com'])->assertOk();

        // Immediate resend → 429
        $this->postJson('/api/v1/auth/verify-email', ['email' => 'limit@example.com'])
            ->assertStatus(429);
    }
}
