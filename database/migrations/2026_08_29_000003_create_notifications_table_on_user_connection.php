<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel notifikasi database Laravel untuk user auth yang hidup di
     * koneksi `sagansa_user` (bukan koneksi default `ev`). Dibutuhkan modul
     * impor Filament: notifikasi selesai impor dikirim via sendToDatabase()
     * ke App\Models\User (koneksi sagansa_user).
     */
    public function up(): void
    {
        Schema::connection('sagansa_user')->create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('sagansa_user')->dropIfExists('notifications');
    }
};
