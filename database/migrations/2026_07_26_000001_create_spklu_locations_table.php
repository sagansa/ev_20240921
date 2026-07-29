<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('spklu_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('external_id')->nullable()->index();
            $table->string('provinsi')->index();
            $table->string('kabupaten_kota')->nullable();
            $table->string('nama_lokasi');
            $table->string('alamat')->nullable();
            $table->decimal('latitude', 12, 8)->nullable();
            $table->decimal('longitude', 12, 8)->nullable();
            $table->string('type_charge')->nullable()->index();
            $table->string('watt')->nullable()->index();
            $table->tinyInteger('status')->default(1);
            $table->string('keterangan')->nullable();
            $table->integer('total_charger')->default(0);
            $table->integer('total_konektor')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('spklu_locations');
    }
};
