<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ESDM Singgat SPKLU — konektor (plug individual).
 *
 * Satu instalasi (mesin) punya banyak konektor
 * (response.spklu[].instalasi[].konektor[]). Tiap konektor punya tipe plug
 * (CCS2, AC Tipe 2, CHAdeMO, dll) dan status operasional.
 *
 * Catatan penting: field `img_konektor` di JSON ESDM adalah base64 PNG inline.
 * Penyelidikan menunjukkan hanya ada 7 gambar unik (1 per tipe plug); sisanya
 * duplikat identik. Gambar TIDAK disimpan di DB — hanya path file hasil ekstrak.
 * File PNG berada di public/storage/esdm/konektor_unique/ (di-upload manual,
 * tidak di-git).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('esdm_singgat_spklu_connectors', function (Blueprint $table) {
            $table->id();
            // Catatan: esdm_id di data ESDM TIDAK unik (7 ID duplikat lintas instalasi),
            // jadi pakai index biasa. Relasi internal tetap via PK 'id'.
            $table->unsignedBigInteger('esdm_id')->index()->comment('konektor.id dari ESDM (bisa duplikat)');
            $table->unsignedBigInteger('installation_id')->comment('FK ke esdm_singgat_spklu_installations.id');
            $table->unsignedBigInteger('installation_esdm_id')->nullable()->index()->comment('spklu_mesin_id dari ESDM (cross-check)');

            $table->string('nama_konektor')->nullable()->index()->comment('CCS2, AC (Tipe 2), CHAdeMO, dll');
            $table->string('status')->nullable()->index()->comment('Beroperasi / dll');
            $table->string('status_konektor')->nullable()->comment('finishing / dll');

            // Path gambar hasil ekstrak (relatif terhadap public/). Hanya 7 unik.
            $table->string('img_path', 128)->nullable()->comment('relatif ke public/, mis. storage/esdm/konektor_unique/CCS2.png');

            $table->timestamps();

            $table->foreign('installation_id')
                ->references('id')
                ->on('esdm_singgat_spklu_installations')
                ->cascadeOnDelete();

            $table->index('installation_id');
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('esdm_singgat_spklu_connectors');
    }
};
