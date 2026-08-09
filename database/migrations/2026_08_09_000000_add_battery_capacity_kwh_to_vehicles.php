<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah `battery_capacity_kwh` ke vehicles — kapasitas baterai efektif
 * kendaraan user (nullable). Dipakai utk estimasi kWh saat metering SPKLU
 * tidak tersedia. Nilai efektif di-resolve via accessor: kolom ini menang,
 * fallback ke typeVehicle.battery_capacity.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->table('vehicles', function (Blueprint $table) {
            $table->double('battery_capacity_kwh')->nullable()->after('license_plate');
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->table('vehicles', function (Blueprint $table) {
            $table->dropColumn('battery_capacity_kwh');
        });
    }
};
