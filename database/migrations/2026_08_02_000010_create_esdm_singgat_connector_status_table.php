<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot status real-time konektor ESDM (nilai TERKINI per konektor).
 *
 * Poller `esdm:poll-status` (tiap 10 menit) mem-fetch ESDM, lalu upsert tabel ini.
 * Relasi ke master data via connector_esdm_id (ID konektor di tabel
 * esdm_singgat_spklu_connectors.esdm_id). Tidak ada FK karena konektor bisa
 * muncul/hilang saat JSON master di-import ulang.
 *
 * Catatan: hanya konektor dengan status_konektor di {available, charging, finishing}
 * yang relevan untuk tracking real-time. unavailable & null TIDAK ditrack per-poll
 * (cukup saat import JSON master).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('esdm_singgat_connector_status', function (Blueprint $table) {
            $table->id();
            // Catatan: connector_esdm_id di data ESDM TIDAK unik (7 ID duplikat lintas
            // instalasi — e.g. ID 6328 muncul 3x). Konsisten dgn master table, pakai
            // index biasa. Identitas unik fisik = (connector_esdm_id, station_esdm_id)
            // atau connector_id lokal bila sudah match ke master.
            $table->unsignedBigInteger('connector_esdm_id')->index()->comment('konektor.id dari ESDM (bisa duplikat)');
            $table->unsignedBigInteger('connector_id')->nullable()->index()->comment('FK opsional ke esdm_singgat_spklu_connectors.id');
            $table->unsignedBigInteger('station_esdm_id')->nullable()->index()->comment('stasiun ESDM untuk filter cepat');

            // Status live dari ESDM
            $table->string('status', 32)->nullable()->comment('Beroperasi / Tidak Beroperasi / Dalam Perbaikan');
            $table->string('status_konektor', 24)->nullable()->index()->comment('available / charging / finishing / unavailable / null');

            // Tracking
            $table->timestamp('status_since')->nullable()->comment('kapan status_konektor terakhir berubah');
            $table->timestamp('last_seen_at')->nullable()->index()->comment('poll terakhir yang melihat konektor ini');
            $table->timestamps();

            // status_konektor & last_seen_at sudah di-index inline di atas.
            // Tidak ada unique constraint: data ESDM punya duplikat identik (e.g.
            // instalasi 5079 muncul 3x dgn konektor 6328 di stasiun 5920). Poller
            // men-dedup saat memproses, tapi tabel tidak memaksakan unikitas.
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('esdm_singgat_connector_status');
    }
};
