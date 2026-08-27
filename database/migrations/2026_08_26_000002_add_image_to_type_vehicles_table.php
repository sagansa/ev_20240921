<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aditif: foto varian kendaraan (nullable) — melengkapi image yang sudah ada
 * di brand_vehicles & model_vehicles. Tidak menyentuh kolom existing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->table('type_vehicles', function (Blueprint $table) {
            $table->string('image')->nullable()->after('model_vehicle_id');
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->table('type_vehicles', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
};
