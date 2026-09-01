<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel CONNECTING — persistensi isi file CONNECTING (master mapping
 * laporan → katalog). Kolom raw_gabungan menyimpan teks mentah
 * "BRAND MODEL TYPE" persis seperti di laporan sebagai acuan/jejak sumber;
 * brand/model/type_vehicle_id = resolusi ke katalog (bisa NULL bila katalog
 * belum ada — dilaporkan oleh vehicle-connecting:verify).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('vehicle_connectings', function (Blueprint $table) {
            $table->id();
            $table->string('raw_gabungan')->unique();
            $table->string('fuel')->nullable();
            $table->unsignedBigInteger('brand_vehicle_id')->nullable()->index();
            $table->unsignedBigInteger('model_vehicle_id')->nullable()->index();
            $table->unsignedBigInteger('type_vehicle_id')->nullable();
            $table->string('powertrain', 8)->nullable();
            $table->string('category')->nullable();
            $table->string('size_class', 16)->nullable();
            $table->timestamps();

            $table->foreign('brand_vehicle_id')->references('id')->on('brand_vehicles')->nullOnDelete();
            $table->foreign('model_vehicle_id')->references('id')->on('model_vehicles')->nullOnDelete();
            $table->foreign('type_vehicle_id')->references('id')->on('type_vehicles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('vehicle_connectings');
    }
};
