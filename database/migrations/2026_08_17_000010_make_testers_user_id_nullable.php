<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tester funnel — izinkan tester non-akun (email gate app Islam).
 *
 * Sebelumnya `user_id` unique + NOT NULL karena tester selalu berasal dari
 * build EV yang wajib login. Email gate (app Islam) mendaftarkan tester hanya
 * dengan email + device_id (tanpa akun EV), jadi `user_id` di-null-kan.
 * Unique index tetap aman: MySQL memperbolehkan banyak NULL pada kolom unique.
 * Row non-akun tetap muncul di Filament + export CSV yang sudah ada (email
 * disimpan langsung di kolom `email`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->table('testers', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Jangan balik ke NOT NULL selama masih ada row non-akun di tabel.
        Schema::connection('ev')->table('testers', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};