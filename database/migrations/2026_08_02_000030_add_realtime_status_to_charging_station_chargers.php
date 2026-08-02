<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom status real-time agregat ke charging_station_chargers (per charger box).
 *
 * ESDM punya status per-KONEKTOR (plug). Satu charger box (instalasi/mesin) bisa
 * punya banyak konektor dgn status berbeda (mis. CCS2 sibuk, AC Tipe 2 kosong).
 * Di level charger box kita simpan AGREGAT (mirip pola station_status), supaya
 * serving API bisa bilang "mesin ini 2 dari 3 plug available" tanpa JOIN ke tabel
 * konektor ESDM. Status granular per-konektor tetap di esdm_singgat_connector_status.
 *
 availability_level:
 *   available — min 1 konektor available
 *   partial   — ada finishing, tidak ada available
 *   occupied  — semua charging
 *   offline   — semua unavailable/null
 *   unknown   — belum ada data poll
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->table('charging_station_chargers', function (Blueprint $table) {
            // Link eksplisit ke instalasi ESDM (utk fold status per-box dari konektor)
            $table->unsignedBigInteger('source_charger_id')->nullable()->after('station_id')->index();

            $table->string('availability_level', 16)->default('unknown')->after('jumlah_konektor')->index();
            $table->unsignedSmallInteger('available_count')->default(0)->after('availability_level');
            $table->unsignedSmallInteger('charging_count')->default(0)->after('available_count');
            $table->unsignedSmallInteger('finishing_count')->default(0)->after('charging_count');
            $table->timestamp('status_updated_at')->nullable()->after('finishing_count');
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->table('charging_station_chargers', function (Blueprint $table) {
            $table->dropColumn([
                'source_charger_id',
                'availability_level', 'available_count',
                'charging_count', 'finishing_count', 'status_updated_at',
            ]);
        });
    }
};
