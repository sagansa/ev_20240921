<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Review kendaraan milik user (fase berikutnya) — tabel disiapkan sekarang
 * agar skrip mobile tidak perlu migrasi ulang saat fitur aktif.
 * vehicle_id menunjuk vehicles.id (UUID, connection ev, sama connection).
 * user_id soft-link tanpa FK (users ada di connection terpisah).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('vehicle_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id')->index();
            $table->uuid('vehicle_id')->index();
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('pros')->nullable();      // kelebihan (bebas/terstruktur nanti)
            $table->text('cons')->nullable();      // kekurangan
            $table->text('body')->nullable();      // review naratif
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vehicle_id', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('vehicle_reviews');
    }
};
