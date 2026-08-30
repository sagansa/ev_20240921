<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel mapping EKSPLISIT raw nama laporan → katalog (brand/model/type).
 * Menjadi lapisan PERTAMA pencocokan di VehicleSalesMatcher — mengatasi
 * varian nama yang tidak tertangkap alias/fuzzy (mis. "WULING-DBG" → Wuling).
 * raw_*_norm = kunci ternormalisasi (hasil VehicleSalesMatcher::normalize),
 * unik berpasangan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('vehicle_name_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('raw_brand');
            $table->string('raw_model');
            $table->string('raw_brand_norm')->index();
            $table->string('raw_model_norm')->index();
            $table->unsignedBigInteger('brand_vehicle_id');
            $table->unsignedBigInteger('model_vehicle_id');
            $table->unsignedBigInteger('type_vehicle_id')->nullable();
            $table->string('catatan')->nullable();
            $table->timestamps();

            $table->unique(['raw_brand_norm', 'raw_model_norm'], 'vehicle_name_mappings_raw_unique');
            $table->foreign('brand_vehicle_id')->references('id')->on('brand_vehicles');
            $table->foreign('model_vehicle_id')->references('id')->on('model_vehicles');
            $table->foreign('type_vehicle_id')->references('id')->on('type_vehicles');
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('vehicle_name_mappings');
    }
};
