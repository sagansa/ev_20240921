<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Review lokasi SPKLU (Fase 1) — data user-sticky yang menunjuk ke
 * charging_stations.id (lapisan kanonik), mengikuti preseden charges.
 *
 * Soft-link (tanpa FK constraint): charging_stations hidup di connection `ev`
 * dan id-nya adalah identitas publik mobile. User direferensikan via user_id
 * (connection user terpisah) — sama seperti `charges.user_id`.
 *
 * Multiple review per user per lokasi DIIZINKAN (tanpa unique constraint).
 * is_anonymous cadangan fase nickname (identitas reviewer tetap anonim).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('station_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('charging_station_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('comment')->nullable();
            $table->boolean('is_anonymous')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['charging_station_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('station_reviews');
    }
};
