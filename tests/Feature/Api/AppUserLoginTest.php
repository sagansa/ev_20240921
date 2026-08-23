<?php

namespace Tests\Feature\Api;

use App\Models\AppUser;
use App\Models\User;
use App\Services\SocialTokenVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Verifikasi atribusi user login via Google & Apple:
 * - app_users dibuat dengan provider & platform benar.
 * - Login kedua increment login_count & update last_login_at.
 * - Response JSON tidak berubah.
 */
class AppUserLoginTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'social@example.com',
        ]);

        Config::set('services.google.client_id', 'web-client-id.apps.googleusercontent.com');
        Config::set('services.google.android_client_id', 'android-client-id.apps.googleusercontent.com');
        Config::set('services.google.ios_client_id', 'ios-client-id.apps.googleusercontent.com');
        Config::set('services.apple.service_id', 'com.example.service');
        Config::set('services.apple.bundle_id', 'com.example.app');
    }

    // ─────────────────────────────────────────────────────────────────────
    // Google Login
    // ─────────────────────────────────────────────────────────────────────

    public function test_google_login_creates_app_user_with_android_platform(): void
    {
        $this->fakeVerifier('google', 'android');

        $this->postJson('/api/v1/auth/google', [
            'id_token' => 'fake-google-token',
        ])->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Google login successful',
            ]);

        $this->assertDatabaseHas('app_users', [
            'user_id' => $this->user->id,
            'provider' => 'google',
            'platform' => 'android',
            'source' => 'login',
            'login_count' => 1,
        ]);
    }

    public function test_google_login_creates_app_user_with_ios_platform(): void
    {
        $this->fakeVerifier('google', 'ios');

        $this->postJson('/api/v1/auth/google', [
            'id_token' => 'fake-google-token',
        ])->assertOk();

        $this->assertDatabaseHas('app_users', [
            'user_id' => $this->user->id,
            'platform' => 'ios',
        ]);
    }

    public function test_google_login_creates_app_user_with_web_platform(): void
    {
        $this->fakeVerifier('google', 'web');

        $this->postJson('/api/v1/auth/google', [
            'id_token' => 'fake-google-token',
        ])->assertOk();

        $this->assertDatabaseHas('app_users', [
            'user_id' => $this->user->id,
            'platform' => 'web',
        ]);
    }

    public function test_google_login_with_null_platform(): void
    {
        $this->fakeVerifier('google', null);

        $this->postJson('/api/v1/auth/google', [
            'id_token' => 'fake-google-token',
        ])->assertOk();

        $this->assertDatabaseHas('app_users', [
            'user_id' => $this->user->id,
            'platform' => null,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Apple Login
    // ─────────────────────────────────────────────────────────────────────

    public function test_apple_login_creates_app_user_with_ios_platform(): void
    {
        $this->fakeVerifier('apple', 'ios');

        $this->postJson('/api/v1/auth/apple', [
            'id_token' => 'fake-apple-token',
        ])->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Apple login successful',
            ]);

        $this->assertDatabaseHas('app_users', [
            'user_id' => $this->user->id,
            'provider' => 'apple',
            'platform' => 'ios',
            'source' => 'login',
            'login_count' => 1,
        ]);
    }

    public function test_apple_login_creates_app_user_with_web_platform(): void
    {
        $this->fakeVerifier('apple', 'web');

        $this->postJson('/api/v1/auth/apple', [
            'id_token' => 'fake-apple-token',
        ])->assertOk();

        $this->assertDatabaseHas('app_users', [
            'user_id' => $this->user->id,
            'platform' => 'web',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Login kedua: increment & update
    // ─────────────────────────────────────────────────────────────────────

    public function test_second_google_login_increments_login_count(): void
    {
        $this->fakeVerifier('google', 'android');

        $this->postJson('/api/v1/auth/google', [
            'id_token' => 'fake-google-token',
        ])->assertOk();

        $firstLogin = AppUser::where('user_id', $this->user->id)->first();
        $this->assertSame(1, $firstLogin->login_count);
        $this->assertNotNull($firstLogin->first_login_at);

        // Timestamp sqlite berpresisi detik — majukan waktu agar last_login_at
        // login kedua pasti berbeda dari login pertama.
        $this->travel(1)->seconds();

        $this->fakeVerifier('google', 'android');

        $this->postJson('/api/v1/auth/google', [
            'id_token' => 'fake-google-token',
        ])->assertOk();

        $secondLogin = AppUser::where('user_id', $this->user->id)->first();
        $this->assertSame(2, $secondLogin->login_count);
        $this->assertTrue($secondLogin->last_login_at->greaterThan($firstLogin->last_login_at));
    }

    public function test_second_apple_login_increments_login_count(): void
    {
        $this->fakeVerifier('apple', 'ios');

        $this->postJson('/api/v1/auth/apple', [
            'id_token' => 'fake-apple-token',
        ])->assertOk();

        $this->fakeVerifier('apple', 'ios');

        $this->postJson('/api/v1/auth/apple', [
            'id_token' => 'fake-apple-token',
        ])->assertOk();

        $appUser = AppUser::where('user_id', $this->user->id)->first();
        $this->assertSame(2, $appUser->login_count);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Response shape tidak berubah
    // ─────────────────────────────────────────────────────────────────────

    public function test_google_login_response_shape_unchanged(): void
    {
        $this->fakeVerifier('google', 'android');

        $this->postJson('/api/v1/auth/google', [
            'id_token' => 'fake-google-token',
        ])
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'avatar'],
                    'token',
                    'token_type',
                ],
            ]);
    }

    public function test_apple_login_response_shape_unchanged(): void
    {
        $this->fakeVerifier('apple', 'ios');

        $this->postJson('/api/v1/auth/apple', [
            'id_token' => 'fake-apple-token',
        ])
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'avatar'],
                    'token',
                    'token_type',
                ],
            ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Token label mengandung platform
    // ─────────────────────────────────────────────────────────────────────

    public function test_token_label_contains_platform(): void
    {
        $this->fakeVerifier('google', 'android');

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => 'fake-google-token',
        ])->assertOk();

        $token = $response->json('data.token');
        $this->assertNotEmpty($token);

        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'ev-android',
            'tokenable_id' => $this->user->id,
        ]);
    }

    public function test_token_label_fallback_when_platform_null(): void
    {
        $this->fakeVerifier('google', null);

        $this->postJson('/api/v1/auth/google', [
            'id_token' => 'fake-google-token',
        ])->assertOk();

        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'ev',
            'tokenable_id' => $this->user->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Invalid token
    // ─────────────────────────────────────────────────────────────────────

    public function test_invalid_google_token_returns_401(): void
    {
        $this->app->instance(SocialTokenVerifier::class, new FakeSocialTokenVerifier('google', null, null));

        $this->postJson('/api/v1/auth/google', [
            'id_token' => 'invalid-token',
        ])->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid Google ID token',
            ]);

        $this->assertDatabaseCount('app_users', 0);
    }

    public function test_invalid_apple_token_returns_401(): void
    {
        $this->app->instance(SocialTokenVerifier::class, new FakeSocialTokenVerifier('apple', null, null));

        $this->postJson('/api/v1/auth/apple', [
            'id_token' => 'invalid-token',
        ])->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid Apple ID token',
            ]);

        $this->assertDatabaseCount('app_users', 0);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    private function fakeVerifier(string $provider, ?string $platform): void
    {
        $payload = [
            'sub' => $provider === 'google' ? 'google-sub-123' : 'apple-sub-123',
            'email' => $this->user->email,
            'name' => $this->user->name,
            'aud' => $this->resolveAud($provider, $platform),
            'iss' => $provider === 'google' ? 'https://accounts.google.com' : 'https://appleid.apple.com',
        ];

        $this->app->instance(SocialTokenVerifier::class, new FakeSocialTokenVerifier($provider, $platform, $payload));
    }

    private function resolveAud(string $provider, ?string $platform): string
    {
        return match ([$provider, $platform]) {
            ['google', 'android'] => Config::get('services.google.android_client_id'),
            ['google', 'ios'] => Config::get('services.google.ios_client_id'),
            ['google', 'web'] => Config::get('services.google.client_id'),
            ['google', null] => 'unknown-aud',
            ['apple', 'ios'] => Config::get('services.apple.bundle_id'),
            ['apple', 'web'] => Config::get('services.apple.service_id'),
            ['apple', null] => 'unknown-aud',
            default => 'unknown',
        };
    }
}
