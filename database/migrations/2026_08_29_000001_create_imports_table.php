<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Disalin dari vendor/filament/actions/database/migrations dengan satu
     * penyesuaian: user_id TIDAK ber-FK, karena user auth (admin) hidup di
     * database `sagansa_user` sedangkan tabel ini ada di `sagansa_ev`
     * (koneksi `ev`) — FK lintas database tidak mungkin dan id tidak
     * dijamin cocok dengan tabel `users` di `ev`.
     */
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('completed_at')->nullable();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('importer');
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('total_rows');
            $table->unsignedInteger('successful_rows')->default(0);
            $table->foreignId('user_id')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};
