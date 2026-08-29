<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aditif (aman utk data existing): klasifikasi jenis & ukuran kendaraan di
 * level MODEL — memisahkan truk & bus dari mobil penumpang.
 * category: taksonomi 14 nilai (lihat App\Support\VehicleCategories);
 * size_class hanya utk kategori ber-ukuran (MPV/Sedan/SUV/Hatchback),
 * kategori lain NULL. Keduanya diisi via impor CSV (sumber kebenaran) atau
 * command vehicle-hierarchy:backfill-category.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->table('model_vehicles', function (Blueprint $table) {
            $table->string('category')->nullable()->index()->after('powertrain');
            $table->string('size_class', 16)->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->table('model_vehicles', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn(['category', 'size_class']);
        });
    }
};
