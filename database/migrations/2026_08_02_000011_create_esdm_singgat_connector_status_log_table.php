<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * History transisi status konektor ESDM — hanya dicatat saat status BERUBAH.
 *
 * Poller membandingkan status_konektor terkini (di esdm_singgat_connector_status)
 * dengan hasil poll baru. Bila berubah, insert 1 baris log + update snapshot.
 *
 * Tidak ada FK ke connector_status karena relasi longgar via connector_esdm_id
 * (konektor bisa muncul/hilang saat JSON master di-import ulang).
 *
 * Contoh query berguna:
 *   - Konektor yang baru selesai charging:
 *       WHERE from_status='charging' AND to_status IN ('available','finishing')
 *   - Stasiun paling sibuk:
 *       SELECT station_esdm_id, count(*) FROM ... WHERE to_status='charging' GROUP BY ...
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('esdm_singgat_connector_status_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('connector_esdm_id')->index();
            $table->unsignedBigInteger('connector_id')->nullable()->index();
            $table->unsignedBigInteger('station_esdm_id')->nullable()->index();

            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24)->nullable()->index();
            $table->string('from_status_konektor', 24)->nullable();
            $table->string('to_status_konektor', 24)->nullable()->index();

            $table->timestamp('observed_at')->nullable()->index()->comment('waktu poll mendeteksi perubahan');
            $table->char('poll_batch', 36)->nullable()->index()->comment('batch UUID poller');
            $table->timestamps();

            // Index dgn nama eksplisit pendek (default Laravel terlalu panjang utk MySQL 64 char)
            $table->index(['connector_esdm_id', 'observed_at'], 'esdl_conn_time_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('esdm_singgat_connector_status_log');
    }
};
