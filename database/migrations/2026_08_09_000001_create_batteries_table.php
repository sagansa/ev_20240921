<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `batteries` — baterai sebagai entitas first-class dgn life-cycle
 * (installed/retired). Menggantikan workaround "buat vehicle baru saat ganti
 * baterai": KM tetap milik kendaraan, baterai dicatat terpasang/lepas per
 * vehicle. SoH & sesi charging di-link ke baterai via FK `battery_id`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('batteries', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('vehicle_id', 36)->index();
            // `user_id` nullable TANPA foreign key — preseden cross-connection:
            // Model User memakai koneksi `sagansa_user`, sementara tabel `users`
            // lokal di koneksi `ev` hanya berisi subset. Data vehicles historis
            // banyak memilik user_id yang tidak match di ev.users (orphan legit).
            // Ownership di-enforce di application layer (Auth::user()->batteries()).
            $table->bigInteger('user_id')->unsigned()->nullable()->index();
            $table->string('label', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->decimal('capacity_kwh', 8, 2)->nullable();
            $table->date('installed_at');
            $table->bigInteger('installed_km')->nullable();
            $table->date('removed_at')->nullable();
            $table->bigInteger('removed_km')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();

            $table->foreign('vehicle_id')
                ->references('id')->on('vehicles')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('batteries');
    }
};
