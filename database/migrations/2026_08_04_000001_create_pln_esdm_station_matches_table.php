<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('pln_esdm_station_matches', function (Blueprint $table) {
            $table->id();

            // Soft-link ke charging_stations.id (source='pln' / source='esdm').
            // TANPA FK constraint — identitas bisa berubah saat rehydrate
            // (preseden: spklu_scrape_raw.linked_spklu_location_id).
            $table->unsignedBigInteger('pln_station_id');
            $table->unsignedBigInteger('esdm_station_id');

            // Snapshot identitas sumber utk audit — pln_charger_locations.id /
            // esdm_singgat_spklu_stations.esdm_id (di-copy dari source_station_id).
            $table->string('pln_source_station_id')->nullable();
            $table->string('esdm_source_station_id')->nullable();
            $table->string('pln_name')->nullable();
            $table->string('esdm_name')->nullable();

            // pending | ai_suggested | approved | rejected | rejected_ai
            $table->string('match_status', 24)->index()->comment('pending|ai_suggested|approved|rejected|rejected_ai');
            // auto_geo | auto_geo_name | ai | manual
            $table->string('match_method', 24)->index()->comment('auto_geo|auto_geo_name|ai|manual');

            $table->unsignedTinyInteger('similarity_pct')->nullable(); // similar_text nama (0-100)
            $table->unsignedInteger('distance_m')->nullable();         // haversine meter
            $table->decimal('ai_confidence', 5, 2)->nullable();        // 0-100, dari AI
            $table->json('ai_reasoning')->nullable();                  // payload AI (reason, signals) utk audit

            $table->string('decided_by')->nullable();                  // user email / 'system' / 'ai'
            $table->timestamp('decided_at')->nullable();
            $table->string('rejected_reason')->nullable();

            $table->timestamps();

            // 1 pasangan (PLN, ESDM) = 1 baris; PLN boleh punya beberapa
            // kandidat rejected/ai_suggested, tapi HANYA 1 approved (constraint
            // di app layer — lihat PlnEsdmMatchService::approve / upsert).
            $table->unique(['pln_station_id', 'esdm_station_id']);
            $table->index(['pln_station_id', 'match_status']);
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('pln_esdm_station_matches');
    }
};
