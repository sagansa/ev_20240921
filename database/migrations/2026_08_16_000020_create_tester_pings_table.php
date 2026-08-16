<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ping tester (append-only) dari channel mana pun — dipakai menghitung
 * "hari aktif" (count distinct date(created_at) per tester) untuk memantau
 * syarat kelulusan 12 tester × 14 hari.
 *
 * tester_id soft-link ke testers.id (nullable: ping bisa datang sebelum
 * user daftar — mis. build store fresh install yang belum login).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('tester_pings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tester_id')->nullable()->index();
            $table->string('device_id')->nullable()->index();
            $table->string('channel'); // ias | store
            $table->string('version_code');
            $table->string('platform')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('tester_pings');
    }
};
