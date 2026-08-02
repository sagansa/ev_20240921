<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah installation_esdm_id ke esdm_singgat_connector_status.
 *
 * Dipakai untuk fold status per-charger box: query agregat group by
 * installation_esdm_id langsung dari connector_status tanpa JOIN.
 * Diisi oleh poller saat proses konektor (instalasi id dari data poll ESDM).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->table('esdm_singgat_connector_status', function (Blueprint $table) {
            $table->unsignedBigInteger('installation_esdm_id')->nullable()->after('station_esdm_id')->index();
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->table('esdm_singgat_connector_status', function (Blueprint $table) {
            $table->dropColumn('installation_esdm_id');
        });
    }
};
