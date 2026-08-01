<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('spklu_scrape_raw', function (Blueprint $table) {
            $table->id();
            $table->string('place_id')->nullable()->index();
            $table->string('nama_lokasi');
            $table->string('alamat')->nullable();
            $table->decimal('latitude', 12, 8)->nullable();
            $table->decimal('longitude', 12, 8)->nullable();
            $table->decimal('rating', 2, 1)->nullable();
            $table->integer('total_reviews')->nullable();
            $table->string('phone')->nullable();
            $table->string('opening_hours')->nullable();
            $table->string('website')->nullable();
            $table->string('provider_name')->nullable();
            $table->char('guessed_provider_id', 36)->nullable()->index();
            $table->string('type_charge')->nullable()->index();
            $table->integer('max_kw')->nullable();
            $table->integer('total_charger')->default(0);
            $table->integer('total_konektor')->default(0);
            $table->json('raw_payload')->nullable();
            $table->char('dedup_hash', 64)->index();
            $table->tinyInteger('status')->default(0);
            $table->unsignedBigInteger('matched_spklu_location_id')->nullable()->index();
            $table->char('scrape_session', 36)->index();
            $table->timestamps();

            $table->unique(['place_id', 'scrape_session']);
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('spklu_scrape_raw');
    }
};
