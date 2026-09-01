<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Powertrain dipindah ke level TYPE (type_vehicles.powertrain) — sumber
 * kebenarannya per varian/type. model_vehicles.powertrain tidak lagi
 * digunakan; model level hanya menyimpan kategori & ukuran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->table('model_vehicles', function (Blueprint $table) {
            $table->dropIndex(['powertrain']);
            $table->dropColumn('powertrain');
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->table('model_vehicles', function (Blueprint $table) {
            $table->string('powertrain', 8)->default('ICE')->index()->after('name');
        });
    }
};
