<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PER-SESSION CHARGER BOX snapshot pada charges.
 *
 * User mobile kini bisa memilih charger box spesifik (mis. "ABB Terra 184 —
 * DC", "Wallbox Pulsar — AC") saat mencatat sesi via ChargingSessionFormView.
 * Snapshot id/nama/type_charge charger box disimpan denormalized agar:
 *   - Filter AC/DC akurat per-sesi (bukan hanya fallback ke station-level).
 *   - Riwayat user stabil walau charging_station_chargers di-rehydrate.
 *
 * Additive-only, nullable — sesi lama/legacy tidak terpengaruh.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->table('charges', function (Blueprint $table) {
            foreach ([
                'station_chargerbox_id_snapshot',
                'station_chargerbox_name_snapshot',
                'station_chargerbox_type_snapshot',
            ] as $col) {
                if (! Schema::connection('ev')->hasColumn('charges', $col)) {
                    $table->string($col)->nullable()->after('station_provider_snapshot');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->table('charges', function (Blueprint $table) {
            $table->dropColumn([
                'station_chargerbox_id_snapshot',
                'station_chargerbox_name_snapshot',
                'station_chargerbox_type_snapshot',
            ]);
        });
    }
};
