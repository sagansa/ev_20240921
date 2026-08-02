<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CANONICAL charging stations — lapisan serving publik yang source-agnostic.
 *
 * Ini adalah read model denormalized: lokasi + info charging + status real-time
 * agregat dalam SATU tabel, sehingga serving cukup query 1 tabel + 1 child.
 * Di-hydrate dari source adaptor (saat ini ESDM Singgat) lewat command
 * `esdm:hydrate-canonical`. Bila source berganti, rehydrate tabel ini dari
 * source baru — contract mobile (GET /api/v1/spklu) tidak berubah.
 *
 * id = identitas publik yang stabil. Fitur user-sticky masa depan (bookmark,
 * logbook, review) akan menunjuk ke charging_stations.id ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('charging_stations', function (Blueprint $table) {
            $table->id();

            // Source link — identitas di sumber asal (mis. esdm_id dari ESDM)
            $table->string('source', 24)->default('esdm')->index();
            $table->string('source_station_id', 64)->nullable();
            $table->unique(['source', 'source_station_id']);

            // Lokasi (geo sudah dibersihkan oleh pipeline source)
            $table->string('nama_lokasi');
            $table->text('alamat')->nullable();
            $table->decimal('latitude', 12, 8)->nullable();
            $table->decimal('longitude', 12, 8)->nullable();
            $table->string('kode_provinsi', 8)->nullable();
            $table->string('provinsi')->nullable()->index();
            $table->string('kabupaten_kota')->nullable();

            // Info charging (agregat roll-up dari instalasi)
            $table->string('type_charge')->nullable()->index();
            $table->string('watt')->nullable();
            $table->unsignedInteger('total_charger')->default(0);
            $table->unsignedInteger('total_konektor')->default(0);

            // Operator / provider
            $table->string('nama_badan_usaha')->nullable();
            $table->char('provider_id', 36)->nullable();
            $table->string('provider_name')->nullable();

            // Tarif & estimasi (raw dari source — investigasi lanjut sebelum dipakai serving)
            $table->string('harga_pengisian')->nullable();
            $table->string('harga_layanan')->nullable();
            $table->decimal('estimasi', 12, 4)->nullable();
            $table->decimal('estimasi_menit', 12, 4)->nullable();
            $table->decimal('jarak', 12, 4)->nullable();

            // Status real-time agregat (di-fold dari poller; tanpa JOIN saat serving)
            $table->string('availability_level', 16)->default('unknown')->index();
            $table->unsignedSmallInteger('available_count')->default(0);
            $table->unsignedSmallInteger('charging_count')->default(0);
            $table->unsignedSmallInteger('finishing_count')->default(0);
            $table->timestamp('status_updated_at')->nullable();

            // Audit: payload mentah dari source
            $table->json('raw_payload')->nullable();

            $table->timestamps();

            $table->foreign('provider_id')
                ->references('id')
                ->on('providers')
                ->nullOnDelete();

            $table->index(['provinsi', 'type_charge']);
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('charging_stations');
    }
};
