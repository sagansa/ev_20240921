<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADAPTASI charges untuk mobile SPKLU — pengganti logbook yang dihapus.
 *
 * Charge awalnya punya FK ke charger_locations (legacy community-submitted)
 * dan mewajibkan vehicle_id + field km/battery. App SPKLU mobile meng-serve
 * dari charging_stations (canonical PLN/ESDM), bukan charger_locations. Untuk
 * reuse sistem Charge sebagai pencatat sesi mobile, kita:
 *
 *   1. Tambah charging_station_id (soft-link ke charging_stations.id, alternatif
 *      charger_location_id) + snapshot identitas station (nama/lat/lng/provider)
 *      agar riwayat user stabil walau station canonical di-rehydrate.
 *
 * Field NOT NULL lama (vehicle_id, km_*, battery) DIPERTAHANKAN di schema —
 * pelonggaran input cepat (vehicle opsional, field parsial) di-handle di
 * controller dengan default value. Ini menghindari modifikasi FK existing yang
 * berisiko pada data Filament admin lama. Additive-only: tidak ada kolom lama
 * yang diubah/dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->table('charges', function (Blueprint $table) {
            // Soft-link ke charging_stations.id (alternatif charger_location_id).
            // Tanpa FK — identitas canonical bisa berubah saat rehydrate, preseden
            // pln_esdm_station_matches. Nullable: sesi lama/legacy tidak punya.
            if (! Schema::connection('ev')->hasColumn('charges', 'charging_station_id')) {
                $table->unsignedBigInteger('charging_station_id')->nullable()->after('charger_id');
                $table->index('charging_station_id', 'charges_charging_station_id_index');
            }

            // Snapshot identitas station — denormalized saat sesi dibuat/diupdate
            // dari charging_stations. Riwayat user tetap utuh walau station
            // diubah/dihapus di kemudian hari.
            foreach ([
                'station_name_snapshot',
                'station_provider_snapshot',
            ] as $col) {
                if (! Schema::connection('ev')->hasColumn('charges', $col)) {
                    $table->string($col)->nullable()->after('charging_station_id');
                }
            }
            foreach ([
                'station_address_snapshot',
            ] as $col) {
                if (! Schema::connection('ev')->hasColumn('charges', $col)) {
                    $table->text($col)->nullable()->after('charging_station_id');
                }
            }
            foreach ([
                'station_lat_snapshot' => [12, 8],
                'station_lng_snapshot' => [12, 8],
            ] as $col => [$p, $s]) {
                if (! Schema::connection('ev')->hasColumn('charges', $col)) {
                    $table->decimal($col, $p, $s)->nullable()->after('charging_station_id');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->table('charges', function (Blueprint $table) {
            $table->dropIndex('charges_charging_station_id_index');
            $table->dropColumn([
                'charging_station_id',
                'station_name_snapshot',
                'station_address_snapshot',
                'station_lat_snapshot',
                'station_lng_snapshot',
                'station_provider_snapshot',
            ]);
        });
    }
};
