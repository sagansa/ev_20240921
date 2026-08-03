<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Layer verifikasi geolokasi ESDM SPKLU — di atas cleaning (geo_status).
 *
 * Cleaning menormalkan koordinat dari *_raw. Verifikasi (esdm:verify-geo)
 * memvalidasi apakah koordinat hasil cleaning masuk akal:
 *  - province_mismatch  : koordinat di luar bbox provinsi kode_provinsi
 *  - verified           : OSM menemukan lokasi < 200m dari koordinat cleaned
 *  - drift_minor/major  : OSM menemukan lokasi 200m–2km / >2km (candidate disimpan)
 *  - not_found          : OSM tidak menemukan lokasi (perlu review manual)
 *  - manual_fixed       : admin mengoreksi manual via Filament (koordinat terbaik)
 *
 * geo_verified_lat/lng adalah koordinat hasil verifikasi (OSM candidate atau
 * koreksi manual) yang dipakai canonical saat hydrate — prioritas:
 * manual_fixed > verified > drift_minor > cleaned > raw.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->table('esdm_singgat_spklu_stations', function (Blueprint $table) {
            $table->string('geo_verification', 24)->nullable()
                ->comment('null|verified|province_mismatch|drift_minor|drift_major|not_found|manual_fixed')
                ->after('geo_notes');
            $table->decimal('geo_verified_lat', 12, 8)->nullable()
                ->comment('koordinat hasil verifikasi (OSM candidate / manual)')
                ->after('geo_verification');
            $table->decimal('geo_verified_lng', 12, 8)->nullable()
                ->after('geo_verified_lat');
            $table->unsignedInteger('geo_distance_m')->nullable()
                ->comment('jarak koordinat cleaned vs verified (meter)')
                ->after('geo_verified_lng');
            $table->string('geo_verified_source', 16)->nullable()
                ->comment('osm|manual|bbox')
                ->after('geo_distance_m');

            $table->index('geo_verification');
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->table('esdm_singgat_spklu_stations', function (Blueprint $table) {
            $table->dropIndex(['geo_verification']);
            $table->dropColumn([
                'geo_verification',
                'geo_verified_lat',
                'geo_verified_lng',
                'geo_distance_m',
                'geo_verified_source',
            ]);
        });
    }
};
