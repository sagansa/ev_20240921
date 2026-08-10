<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Foto lokasi SPKLU (Fase 2) — galeri foto per lokasi, terpisah dari review.
 *
 * Satu row = satu foto. Soft-link ke charging_stations.id (lapisan kanonik),
 * mengikuti preseden charges & station_reviews. `path` menyimpan path relatif
 * di disk `public` (mis. "station-photos/42/abc.jpg") — di-resolve ke URL
 * "/storage/..." oleh StationPhotoResource.
 *
 * Gate sama dgn review: user wajib pernah menyelesaikan sesi charging di
 * station tsb (di-enforce di controller, bukan di skema). Multiple foto per
 * user per lokasi diizinkan.
 *
 * Tanpa caption (keputusan spec Fase 2). Tanpa FK constraint (cross-DB user,
 * canonical rehydrate).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('station_photos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('charging_station_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('path'); // path relatif di disk `public`
            $table->timestamps();
            $table->softDeletes(); // admin hapus (moderasi)

            $table->index(['charging_station_id', 'created_at']); // serve galeri by station
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('station_photos');
    }
};
