<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah `meter_before` + `tariff_id` ke charges — dukungan resume sesi
 * belum-selesai mobile.
 *
 * `meter_before`: meter kWh saat sesi dimulai (input cepat lapangan, persist
 * antar pembukaan form saat sesi masih berjalan). `tariff_id`: id golongan
 * PLN (`r1-1300-2200` dst, string — konsisten dgn PlnTariffTable) dipakai
 * utk menghitung estimasi biaya sesi berikutnya.
 *
 * Additive-only, nullable — sesi lama/legacy tidak terpengaruh.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->table('charges', function (Blueprint $table) {
            if (! Schema::connection('ev')->hasColumn('charges', 'meter_before')) {
                $table->double('meter_before')->nullable()->after('is_kwh_measured');
            }

            if (! Schema::connection('ev')->hasColumn('charges', 'tariff_id')) {
                $table->string('tariff_id', 100)->nullable()->after('meter_before');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->table('charges', function (Blueprint $table) {
            if (Schema::connection('ev')->hasColumn('charges', 'tariff_id')) {
                $table->dropColumn('tariff_id');
            }

            if (Schema::connection('ev')->hasColumn('charges', 'meter_before')) {
                $table->dropColumn('meter_before');
            }
        });
    }
};
