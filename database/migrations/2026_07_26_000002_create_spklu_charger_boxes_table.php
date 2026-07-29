<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('spklu_charger_boxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spklu_location_id')->constrained('spklu_locations')->cascadeOnDelete();
            $table->string('chargerbox_id')->nullable()->index();
            $table->string('type_charge')->nullable();
            $table->string('nama_chargerbox')->nullable();
            $table->string('watt')->nullable();
            $table->integer('jumlah_charger')->default(1);
            $table->string('jumlah_konektor')->default('1');
            $table->string('icon')->nullable();
            $table->string('gambar')->nullable();
            $table->timestamps();

            $table->index('spklu_location_id');
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('spklu_charger_boxes');
    }
};
