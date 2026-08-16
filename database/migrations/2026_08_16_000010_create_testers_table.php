<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tester funnel (Closed Testing Play Console) — siapa yang register via build
 * Internal App Sharing (IAS) + siapa yang sudah aktif di build testing (ping
 * dari channel `store`).
 *
 * Soft-link user_id (tanpa FK constraint): user hidup di connection
 * `sagansa_user` yang terpisah — sama seperti `station_reviews.user_id`.
 * Email di-copy dari users saat register supaya panel tidak perlu join
 * lintas DB.
 *
 * Catatan jujur (sesuai spec): keanggotaan track TIDAK bisa diverifikasi via
 * API Play Console (tidak ada). Ping build `store` adalah proxy terbaik;
 * kelulusan resmi 12 tester × 14 hari tetap dilihat di Play Console.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('testers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('email');
            $table->string('platform')->nullable();
            $table->string('source')->default('internal_app_sharing');
            $table->string('device_id')->nullable()->index();
            $table->string('status')->default('registered'); // registered | store_active
            $table->timestamp('first_store_ping_at')->nullable();
            $table->timestamp('last_ping_at')->nullable();
            $table->string('last_ping_version_code')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('testers');
    }
};
