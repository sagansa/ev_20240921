<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ESDM Singgat SPBKLU (stasiun penukaran BATERAI untuk kendaraan RODA 2/motor).
 *
 * Sumber: POST https://gatrik.esdm.go.id/singgat/api/api/get-lokasi
 * Path: response.spbklu[] — 267 stasiun.
 *
 * Beda dari SPKLU: SPBKLU tidak mengisi daya, melainkan menukar baterai.
 * Struktur nested: stasiun → kabinet[] → baterai[].
 *
 * Catatan koordinat: SPBKLU menyimpan lat/lng sebagai INTEGER tanpa titik desimal
 * dan jumlah digit tidak konsisten (mis. "-6242764" = -6.242764 tapi "10677203"
 * bisa berarti 106.77203 atau 106.772030). Migration cleaning akan menangani.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('esdm_singgat_spbklu_stations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('esdm_id')->unique()->comment('id dari ESDM (response.spbklu[].id)');

            $table->string('nama_stasiun');
            $table->text('alamat_spbklu')->nullable();
            $table->string('kode_provinsi', 4)->nullable()->index();
            $table->string('kode_kota', 4)->nullable()->index();
            $table->string('nama_badan_usaha')->nullable()->index();
            $table->string('nomor_identitas')->nullable()->index();

            // Koordinat sumber (string raw — korup/tanpa desimal)
            $table->string('latitude_spbklu_raw', 32)->nullable();
            $table->string('longitude_spbklu_raw', 32)->nullable();

            $table->unsignedInteger('count_battery')->default(0);
            $table->decimal('estimasi', 12, 4)->nullable();
            $table->decimal('estimasi_menit', 12, 4)->nullable();
            $table->text('encrypt_id')->nullable();

            // Hasil normalisasi (diisi cleaning migration)
            $table->decimal('latitude', 12, 8)->nullable();
            $table->decimal('longitude', 12, 8)->nullable();
            $table->string('geo_status', 24)->nullable();
            $table->text('geo_notes')->nullable();

            $table->json('raw_payload')->nullable();
            $table->char('import_batch', 36)->nullable()->index();

            $table->timestamps();

            $table->index(['kode_provinsi', 'kode_kota']);
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('esdm_singgat_spbklu_stations');
    }
};
