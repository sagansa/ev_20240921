<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mencatat asal login user EV (Google/Apple) dan platform (Android/iOS/Web).
 *
 * Tabel ini berada di koneksi `sagansa_ev` — terpisah dari `sagansa_user`
 * yang dibagikan oleh semua apps. user_id direferensikan tanpa FK lintas DB.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('app_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('provider', 10)->nullable()->comment('google / apple');
            $table->string('platform', 10)->nullable()->comment('android / ios / web');
            $table->string('source', 10)->default('login')->comment('login / backfill');
            $table->unsignedInteger('login_count')->default(1);
            $table->timestamp('first_login_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->index('provider');
            $table->index('platform');
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('app_users');
    }
};
