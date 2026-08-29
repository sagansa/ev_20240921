<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tautan opsional statistik penjualan ke type_vehicles (varian penuh hasil
 * split "TYPE MODEL" GAIKINDO). Nullable tanpa FK constraint — senada dengan
 * brand_vehicle_id/model_vehicle_id: menghapus/mengubah katalog tidak merusak
 * statistik historis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->table('vehicle_sales_stats', function (Blueprint $table): void {
            $table->unsignedBigInteger('type_vehicle_id')->nullable()->index()->after('model_vehicle_id');
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->table('vehicle_sales_stats', function (Blueprint $table): void {
            $table->dropIndex(['type_vehicle_id']);
            $table->dropColumn('type_vehicle_id');
        });
    }
};
