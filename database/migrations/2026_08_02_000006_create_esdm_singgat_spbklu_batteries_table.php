<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ESDM Singgat SPBKLU — baterai (item individual di dalam kabinet).
 *
 * response.spbklu[].kabinet[].baterai[] — 1.581 baterai total di dataset.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('esdm_singgat_spbklu_batteries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('esdm_id')->unique()->comment('baterai.id dari ESDM');
            $table->unsignedBigInteger('cabinet_id')->comment('FK ke esdm_singgat_spbklu_cabinets.id');
            $table->unsignedBigInteger('cabinet_esdm_id')->nullable()->index()->comment('spbklu_kabinet_id dari ESDM (cross-check)');

            $table->string('merek_baterai')->nullable()->index();
            $table->string('tipe_baterai')->nullable()->comment('Li-ion / Li ion / dll (data tidak konsisten)');
            $table->string('kapasitas_baterai_raw', 16)->nullable()->comment('string asli dari ESDM');
            $table->string('status_baterai')->nullable()->index()->comment('air / Air / dll (data tidak konsisten kapitalisasi)');
            $table->string('persentase_raw', 8)->nullable()->comment('string asli');

            $table->timestamps();

            $table->foreign('cabinet_id')
                ->references('id')
                ->on('esdm_singgat_spbklu_cabinets')
                ->cascadeOnDelete();

            $table->index('cabinet_id');
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('esdm_singgat_spbklu_batteries');
    }
};
