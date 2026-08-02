<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agregat status real-time per STASIUN ESDM (derived dari konektor).
 *
 * ESDM tidak melaporkan status stasiun secara langsung — status ada per konektor.
 * Tabel ini menyimpan ringkasan agregat (jumlah plug per status) yang dihitung
 * saat poller berjalan, supaya query "stasiun available" tidak perlu join berat
 * ke tabel konektor.
 *
 * Relasi longgar via station_esdm_id (FK ke esdm_singgat_spklu_stations.esdm_id,
 * tanpa constraint karena identitas bisa berubah saat import JSON master ulang).
 *
 * availability_level:
 *   - available    : min. 1 konektor available
 *   - partial      : tidak ada available, tapi ada finishing (segera bebas)
 *   - occupied     : semua konektor charging (penuh)
 *   - offline      : semua unavailable/null (tidak dilacak real-time)
 *   - unknown      : belum ada data poll
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('esdm_singgat_station_status', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('station_esdm_id')->unique()->comment('spklu.id dari ESDM');
            $table->unsignedBigInteger('station_id')->nullable()->index()->comment('FK opsional ke esdm_singgat_spklu_stations.id');

            // Hitungan per status (real-time)
            $table->unsignedSmallInteger('total_connectors')->default(0);
            $table->unsignedSmallInteger('available_count')->default(0);
            $table->unsignedSmallInteger('charging_count')->default(0);
            $table->unsignedSmallInteger('finishing_count')->default(0);
            $table->unsignedSmallInteger('unavailable_count')->default(0);
            $table->unsignedSmallInteger('unknown_count')->default(0)->comment('status_konektor null');

            // Derived
            $table->string('availability_level', 16)->default('unknown')->index();
            $table->timestamp('aggregated_at')->nullable()->index()->comment('waktu poll terakhir');

            $table->timestamps();

            // availability_level & aggregated_at sudah di-index inline di atas
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('esdm_singgat_station_status');
    }
};
