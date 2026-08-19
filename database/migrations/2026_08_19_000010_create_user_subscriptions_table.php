<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entitlement langganan server-side, terikat ke AKUN APP (user), bukan ke
 * store account (Apple ID / Google Play account).
 *
 * Tujuan: "ganti akun → entitlement tidak ikut pindah". Pro hanya dimiliki
 * user yang melakukan purchase. Saat user login di device lain, server
 * menentukan entitlement-nya dari tabel ini (bukan dari StoreKit lokal).
 *
 * Idempotency key: (user_id, provider, original_transaction_id) — user yang
 * sama tidak boleh meregistrasi transaksi yang sama dua kali. Tanpa FK
 * constraint (cross-DB user), mengikuti preseden saved_stations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('provider');                 // 'apple' | 'google'
            $table->string('product_id');
            $table->string('original_transaction_id');  // Apple originalTransactionId / Google purchaseToken
            $table->string('store_transaction_id')->nullable(); // transaction terbaru / purchase token aktif
            $table->string('status')->default('active'); // active|expired|refunded|revoked|cancelled
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('auto_renewing')->default(false);
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->unique(
                ['user_id', 'provider', 'original_transaction_id'],
                'user_subs_user_provider_original_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('user_subscriptions');
    }
};