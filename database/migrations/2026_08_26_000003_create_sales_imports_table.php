<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat import data penjualan kendaraan (GAIKINDO wholesales xlsx).
 * Satu file = satu baris di sini; angka bulanannya tersimpan ter-normalisasi
 * di vehicle_sales_stats. Import BERSIFAT APPEND-ONLY: tidak pernah mengubah
 * katalog brand/model/type maupun baris import sebelumnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('sales_imports', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('source')->default('gaikindo'); // gaikindo | manual | ...
            $table->unsignedSmallInteger('year');          // tahun periode utama
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->string('status', 16)->default('processed'); // processed|partial|failed
            $table->json('meta')->nullable();              // ringkasan: sheet→segmen, unmatched, dsb.
            $table->timestamps();

            $table->index(['year', 'source']);
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->dropIfExists('sales_imports');
    }
};
