<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('spklu_scrape_raw_chargers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scrape_raw_id')->constrained('spklu_scrape_raw')->cascadeOnDelete();
            $table->string('connector_type')->nullable();
            $table->integer('power_kw')->nullable();
            $table->string('watt')->nullable();
            $table->string('type_charge')->nullable();
            $table->integer('jumlah_charger')->default(1);
            $table->string('jumlah_konektor')->default('1');
            $table->timestamps();

            $table->index('scrape_raw_id');
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('spklu_scrape_raw_chargers');
    }
};
