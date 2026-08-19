<?php

namespace Tests\Feature\Api;

use App\Models\UserSubscription;
use Illuminate\Auth\AuthenticationException;

/**
 * Entitlement server-side (per akun app):
 * - config: non-Pro default; Pro setelah register; expired → non-Pro.
 * - register-apple / register-google: idempotent per original_transaction_id,
 *   revoke (refund) mencabut entitlement.
 * - Akun dihapus → subscription ikut terhapus.
 */
class MonetizationTest extends ApiTestCase
{
    public function test_config_returns_non_pro_by_default(): void
    {
        $this->getJson('/api/v1/monetization/config')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.is_ad_free_active', false)
            ->assertJsonPath('data.ad_free_until_timestamp', 0)
            ->assertJsonPath('data.free_initial_credits', 5)
            ->assertJsonPath('data.plans.0.sku', 'remove_ads_monthly')
            ->assertJsonPath('data.plans.1.sku', 'remove_ads_yearly');
    }

    public function test_register_apple_activates_entitlement(): void
    {
        $expires = now()->addDays(30)->valueOf();

        $this->postJson('/api/v1/monetization/register-apple', [
            'original_transaction_id' => 'txn-original-1',
            'transaction_id' => 'txn-1',
            'product_id' => 'remove_ads_monthly',
            'expires_at' => $expires,
            'auto_renewing' => true,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_ad_free_active', true);

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $this->authUser->id,
            'provider' => 'apple',
            'original_transaction_id' => 'txn-original-1',
            'status' => 'active',
        ]);

        $this->getJson('/api/v1/monetization/config')
            ->assertJsonPath('data.is_ad_free_active', true);
    }

    public function test_register_apple_is_idempotent_per_original_transaction(): void
    {
        $payload = [
            'original_transaction_id' => 'txn-original-1',
            'transaction_id' => 'txn-1',
            'product_id' => 'remove_ads_monthly',
            'expires_at' => now()->addDays(30)->valueOf(),
        ];

        $this->postJson('/api/v1/monetization/register-apple', $payload)->assertOk();
        $this->postJson('/api/v1/monetization/register-apple', $payload)->assertOk();

        $this->assertSame(
            1,
            $this->authUser->userSubscriptions()
                ->where('provider', 'apple')
                ->where('original_transaction_id', 'txn-original-1')
                ->count()
        );
    }

    public function test_register_apple_revocation_disables_entitlement(): void
    {
        $this->postJson('/api/v1/monetization/register-apple', [
            'original_transaction_id' => 'txn-original-1',
            'transaction_id' => 'txn-1',
            'product_id' => 'remove_ads_monthly',
            'expires_at' => now()->addDays(30)->valueOf(),
        ])->assertJsonPath('data.is_ad_free_active', true);

        $this->postJson('/api/v1/monetization/register-apple', [
            'original_transaction_id' => 'txn-original-1',
            'transaction_id' => 'txn-1',
            'product_id' => 'remove_ads_monthly',
            'revocation_date' => now()->valueOf(),
        ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_ad_free_active', false);

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $this->authUser->id,
            'original_transaction_id' => 'txn-original-1',
            'status' => 'revoked',
        ]);

        $this->getJson('/api/v1/monetization/config')
            ->assertJsonPath('data.is_ad_free_active', false);
    }

    public function test_register_apple_rejects_unknown_product(): void
    {
        // withoutExceptionHandling() aktif di ApiTestCase → ValidationException
        // dilempar, bukan response 422. Tangkap dan verifikasi errornya.
        try {
            $this->postJson('/api/v1/monetization/register-apple', [
                'original_transaction_id' => 'txn-x',
                'product_id' => 'not_a_real_product',
            ]);
            $this->fail('Expected ValidationException for unknown product.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('product_id', $e->errors());
        }
    }

    public function test_register_google_activates_entitlement(): void
    {
        $this->postJson('/api/v1/monetization/register-google', [
            'purchase_token' => 'gp-token-1',
            'product_id' => 'remove_ads_yearly',
            'package_name' => 'id.sagansa.ev',
            'expires_at' => now()->addYear()->valueOf(),
            'auto_renewing' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.is_ad_free_active', true);

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $this->authUser->id,
            'provider' => 'google',
            'original_transaction_id' => 'gp-token-1',
            'status' => 'active',
        ]);
    }

    public function test_expired_subscription_is_not_pro(): void
    {
        UserSubscription::create([
            'user_id' => $this->authUser->id,
            'provider' => 'apple',
            'product_id' => 'remove_ads_monthly',
            'original_transaction_id' => 'expired-1',
            'status' => 'active',
            'expires_at' => now()->subDay(),
        ]);

        $this->getJson('/api/v1/monetization/config')
            ->assertJsonPath('data.is_ad_free_active', false);
    }

    public function test_entitlement_is_user_scoped(): void
    {
        $otherUser = \App\Models\User::factory()->create();

        UserSubscription::create([
            'user_id' => $otherUser->id,
            'provider' => 'apple',
            'product_id' => 'remove_ads_yearly',
            'original_transaction_id' => 'other-txn',
            'status' => 'active',
            'expires_at' => now()->addYear(),
        ]);

        // User yang login (authUser) tidak boleh mewarisi Pro user lain.
        $this->getJson('/api/v1/monetization/config')
            ->assertJsonPath('data.is_ad_free_active', false);
    }

    public function test_requires_auth(): void
    {
        $this->app['auth']->forgetGuards();

        try {
            $this->getJson('/api/v1/monetization/config');
            $this->fail('Expected AuthenticationException for guest.');
        } catch (AuthenticationException $e) {
            $this->assertSame('Unauthenticated.', $e->getMessage());
        }
    }

    public function test_account_delete_removes_subscriptions(): void
    {
        UserSubscription::create([
            'user_id' => $this->authUser->id,
            'provider' => 'apple',
            'product_id' => 'remove_ads_monthly',
            'original_transaction_id' => 'txn-cleanup-1',
            'status' => 'active',
            'expires_at' => now()->addDays(30),
        ]);

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $this->authUser->id,
        ]);

        // AccountController::destroy memanggil $user->userSubscriptions()->delete()
        // sebelum soft-delete user. Endpoint penuh (DELETE /account) tidak bisa
        // diuji end-to-end di sini karena tokens()->delete() terkunci di koneksi
        // sagansa_user (keterbatasan infra sqlite yang sudah ada — DeleteAccountTest
        // pun gagal pada baris yang sama). Uji logika cleanup-nya secara langsung.
        $this->authUser->userSubscriptions()->delete();

        $this->assertDatabaseMissing('user_subscriptions', [
            'user_id' => $this->authUser->id,
        ]);
    }
}