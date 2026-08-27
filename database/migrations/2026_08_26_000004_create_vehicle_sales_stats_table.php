<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Statistik penjualan bulanan per model — hasil normalisasi file GAIKINDO.
 *
 * Prinsip aman-data:
 * - raw_brand/raw_model SELALU tersimpan apa adanya dari file sumber.
 * - brand_vehicle_id / model_vehicle_id adalah hasil fuzzy-match READ-ONLY
 *   ke katalog existing (nullable, tanpa FK constraint agar menghapus/mengubah
 *   katalog tidak merusak statistik historis).
 * - powertrain: BEV|PHEV|HEV|ICE hasil klasifikasi otomatis + koreksi admin.
 * - month nullable → baris agregat tahunan (total) boleh ikut tersimpan,
 *   query tren selalu filter month IS NOT NULL bila butuh granularitas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('vehicle_sales_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_import_id')->constrained('sales_imports')->cascadeOnDelete();
            $table->string('raw_brand')->index();
            $table->string('raw_model');
            $table->unsignedBigInteger('brand_vehicle_id')->nullable()->index();
            $table->unsignedBigInteger('model_vehicle_id')->nullable()->index();
            $table->string('segment')->nullable();      // Sedan | 4X2 | PU/Truck | Bus | dst.
            $table->string('powertrain', 8)->default('ICE')->index(); // BEV|PHEV|HEV|ICE
            $table->unsignedSmallInteger('year')->index();
            $table->unsignedTinyInteger('month')->nullable(); // 1..12
            $table->unsignedInteger('units')->default(0);
            $table->string('origin_country')->nullable();
            $table->timestamps();

            $table->index(['year', 'month', 'powertrain']);
            $table->index(['brand_vehicle_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('vehicle_sales_stats');
    }
};
