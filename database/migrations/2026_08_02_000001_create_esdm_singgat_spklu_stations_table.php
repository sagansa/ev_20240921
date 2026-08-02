<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ESDM Singgat SPKLU (stasiun pengisian kendaraan listrik MOBIL).
 *
 * Sumber: POST https://gatrik.esdm.go.id/singgat/api/api/get-lokasi
 * Body payload: {} (return-all, ~3.136 stasiun).
 *
 * Skema ini disalin 1:1 dari field JSON ESDM — termasuk kasiswa tipedata
 * string untuk latitude/longitude (data sumber korup/tukar-tukar, akan
 * dinormalisasi di migration cleaning terpisah). Tabel ini SENGAJA terpisah
 * dari spklu_locations / spklu_scrape_raw agar pipeline ESDM berdiri sendiri
 * dan tidak mengganggu sistem yang ada.
 *
 * Catatan: koordinat sumber sering tertukar/kehilangan digit. Field lat/lng
 * di sini menyimpan nilai APA ADANYA dari ESDM (string). Migration cleaning
 * akan menambah kolom lat_clean/lng_clean dan status_geo untuk data terkoreksi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('esdm_singgat_spklu_stations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('esdm_id')->unique()->comment('id dari ESDM (response.spklu[].id)');

            // Data lokasi (1:1 dengan JSON ESDM)
            $table->string('nama_stasiun');
            $table->text('alamat_spklu')->nullable();
            $table->string('kode_provinsi', 4)->nullable()->index();
            $table->string('kode_kota', 4)->nullable()->index();
            $table->string('nama_badan_usaha')->nullable()->index()->comment('operator/provider');
            $table->string('latitude_spklu_raw', 32)->nullable()->comment('nilai asli string dari ESDM (sering korup)');
            $table->string('longitude_spklu_raw', 32)->nullable()->comment('nilai asli string dari ESDM (sering korup)');

            // Agregat
            $table->unsignedInteger('count_konektor')->default(0);
            $table->decimal('estimasi', 12, 4)->nullable()->comment('estimasi biaya (Rp?)');
            $table->decimal('estimasi_menit', 12, 4)->nullable();

            // ID terenkripsi ESDM (untuk deep-link ke portal bila perlu)
            $table->text('encrypt_id')->nullable();

            // Kolom koordinat hasil normalisasi (diisi oleh migration cleaning)
            $table->decimal('latitude', 12, 8)->nullable()->comment('diisi setelah cleaning');
            $table->decimal('longitude', 12, 8)->nullable()->comment('diisi setelah cleaning');
            $table->string('geo_status', 24)->nullable()->comment('ok|swapped|fixed_digits|unresolved|null');
            $table->text('geo_notes')->nullable();

            // Simpan fasilitas sebagai JSON (array of object dari ESDM, biasanya kosong)
            $table->json('fasilitas')->nullable();

            // Audit: full record mentah untuk追溯ibilitas
            $table->json('raw_payload')->nullable();

            // Batch import tracking
            $table->char('import_batch', 36)->nullable()->index();

            $table->timestamps();

            // nama_badan_usaha & kode_provinsi sudah di-index inline di atas
            $table->index(['kode_provinsi', 'kode_kota']);
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('esdm_singgat_spklu_stations');
    }
};
