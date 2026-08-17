<?php

namespace Tests\Feature\Api;

use App\Models\Tester;
use App\Models\TesterPing;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Tester funnel (Closed Testing):
 * - POST /api/v1/testers/register  — auth, idempotent, email snapshot.
 * - POST /api/v1/testers/ping      — publik, Bearer opsional (user/device match).
 * - GET  /api/v1/app/config        — publik, shape.
 */
class TesterFunnelTest extends ApiTestCase
{
    private function registerTester(array $overrides = []): Tester
    {
        return Tester::create(array_merge([
            'user_id' => $this->authUser->id,
            'email' => $this->authUser->email,
        ], $overrides));
    }

    // ─────────────────────────────────────────────────────────────────────
    // Register
    // ─────────────────────────────────────────────────────────────────────

    public function test_register_requires_auth(): void
    {
        $this->app['auth']->forgetGuards();

        try {
            $this->postJson('/api/v1/testers/register', ['device_id' => 'abc']);
            $this->fail('Expected AuthenticationException for guest.');
        } catch (AuthenticationException $e) {
            $this->assertSame('Unauthenticated.', $e->getMessage());
        }
    }

    public function test_register_creates_tester_with_email_snapshot(): void
    {
        $this->postJson('/api/v1/testers/register', [
            'device_id' => 'dev-123',
            'platform' => 'android',
        ])->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'email' => $this->authUser->email,
                    'status' => 'registered',
                ],
            ]);

        $this->assertDatabaseHas('testers', [
            'user_id' => $this->authUser->id,
            'email' => $this->authUser->email,
            'device_id' => 'dev-123',
            'platform' => 'android',
            'status' => 'registered',
            'source' => 'internal_app_sharing',
        ]);
    }

    public function test_register_is_idempotent_by_user_id(): void
    {
        $this->postJson('/api/v1/testers/register', ['device_id' => 'dev-a'])->assertStatus(201);
        $this->postJson('/api/v1/testers/register', ['device_id' => 'dev-b'])->assertStatus(201);

        $this->assertSame(1, Tester::where('user_id', $this->authUser->id)->count());

        // updateOrCreate memperbarui device_id, bukan membuat baris baru.
        $this->assertDatabaseHas('testers', [
            'user_id' => $this->authUser->id,
            'device_id' => 'dev-b',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Register email (app Islam, publik tanpa login)
    // ─────────────────────────────────────────────────────────────────────

    public function test_register_email_is_public_and_creates_tester(): void
    {
        $this->app['auth']->forgetGuards();

        $this->postJson('/api/v1/testers/register-email', [
            'email' => 'tester@example.com',
            'device_id' => 'dev-islam-1',
            'platform' => 'android',
        ])->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'email' => 'tester@example.com',
                    'status' => 'registered',
                ],
            ]);

        $this->assertDatabaseHas('testers', [
            'email' => 'tester@example.com',
            'device_id' => 'dev-islam-1',
            'platform' => 'android',
            'source' => 'islam_email_gate',
            'user_id' => null,
        ]);
    }

    public function test_register_email_is_idempotent_by_device_id(): void
    {
        $this->app['auth']->forgetGuards();

        $this->postJson('/api/v1/testers/register-email', [
            'email' => 'first@example.com',
            'device_id' => 'dev-islam-2',
        ])->assertStatus(201);
        $this->postJson('/api/v1/testers/register-email', [
            'email' => 'second@example.com',
            'device_id' => 'dev-islam-2',
        ])->assertStatus(201);

        $this->assertSame(1, Tester::where('device_id', 'dev-islam-2')->count());
        $this->assertDatabaseHas('testers', [
            'device_id' => 'dev-islam-2',
            'email' => 'second@example.com',
        ]);
    }

    public function test_register_email_validates_invalid_email(): void
    {
        $this->app['auth']->forgetGuards();

        try {
            $this->postJson('/api/v1/testers/register-email', [
                'email' => 'not-an-email',
                'device_id' => 'dev-islam-3',
            ]);
            $this->fail('Expected ValidationException for invalid email.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('email', $e->errors());
        }
    }

    public function test_register_email_requires_device_id(): void
    {
        $this->app['auth']->forgetGuards();

        try {
            $this->postJson('/api/v1/testers/register-email', [
                'email' => 'tester@example.com',
            ]);
            $this->fail('Expected ValidationException for missing device_id.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('device_id', $e->errors());
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Ping
    // ─────────────────────────────────────────────────────────────────────

    public function test_ping_with_token_matches_by_user_id(): void
    {
        $this->registerTester();

        $this->postJson('/api/v1/testers/ping', [
            'device_id' => 'dev-1',
            'channel' => 'store',
            'version_code' => '20',
            'platform' => 'android',
        ])->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'tracked' => true,
                    'status' => 'store_active',
                ],
            ]);

        $this->assertDatabaseHas('testers', [
            'user_id' => $this->authUser->id,
            'status' => 'store_active',
            'last_ping_version_code' => '20',
        ]);

        $tester = Tester::where('user_id', $this->authUser->id)->first();
        $this->assertNotNull($tester->first_store_ping_at);
        $this->assertNotNull($tester->last_ping_at);

        $this->assertSame(1, TesterPing::count());
    }

    public function test_ping_anonymous_matches_by_device_id(): void
    {
        $this->registerTester(['device_id' => 'dev-match', 'user_id' => 999]);

        $this->app['auth']->forgetGuards();

        $this->postJson('/api/v1/testers/ping', [
            'device_id' => 'dev-match',
            'channel' => 'store',
            'version_code' => '20',
        ])->assertStatus(201)
            ->assertJsonPath('data.tracked', true);

        $this->assertDatabaseHas('testers', [
            'device_id' => 'dev-match',
            'status' => 'store_active',
        ]);
    }

    public function test_ping_anonymous_without_match_still_records(): void
    {
        $this->app['auth']->forgetGuards();

        $this->postJson('/api/v1/testers/ping', [
            'device_id' => 'unknown-device',
            'channel' => 'ias',
            'version_code' => '19',
        ])->assertStatus(201)
            ->assertJsonPath('data.tracked', false);

        $this->assertDatabaseHas('tester_pings', [
            'device_id' => 'unknown-device',
            'channel' => 'ias',
            'version_code' => '19',
            'tester_id' => null,
        ]);
    }

    public function test_ping_tracks_active_days(): void
    {
        $this->registerTester();
        $tester = Tester::where('user_id', $this->authUser->id)->first();

        $yesterday = TesterPing::create(['tester_id' => $tester->id, 'device_id' => 'x', 'channel' => 'store', 'version_code' => '20']);
        $yesterday->created_at = now()->subDays(1);
        $yesterday->save();

        TesterPing::create(['tester_id' => $tester->id, 'device_id' => 'x', 'channel' => 'store', 'version_code' => '20']);

        $this->assertSame(2, $tester->refresh()->active_days);
    }

    public function test_ping_channel_validation(): void
    {
        try {
            $this->postJson('/api/v1/testers/ping', [
                'device_id' => 'd',
                'channel' => 'bogus',
                'version_code' => '19',
            ]);
            $this->fail('Expected ValidationException for invalid channel.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('channel', $e->errors());
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // App config
    // ─────────────────────────────────────────────────────────────────────

    public function test_app_config_is_public_with_expected_shape(): void
    {
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/v1/app/config')
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'min_version_code',
                    'latest_version_name',
                    'update_message',
                    'update_url',
                    'track_build_usage',
                    'latest_store_version_code',
                    'min_version_code_ios',
                    'update_url_ios',
                ],
            ]);
    }

    public function test_admin_can_export_csv_email_list(): void
    {
        Role::findOrCreate('admin', 'web');
        $this->authUser->assignRole('admin');
        $this->registerTester();

        $response = $this->get('/admin/testers/export');
        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString($this->authUser->email, $response->streamedContent());
        $this->assertStringContainsString('Email', $response->streamedContent());
    }

    public function test_export_csv_forbidden_for_non_admin(): void
    {
        try {
            $this->get('/admin/testers/export');
            $this->fail('Expected HttpException(403) for non-admin.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }
}
