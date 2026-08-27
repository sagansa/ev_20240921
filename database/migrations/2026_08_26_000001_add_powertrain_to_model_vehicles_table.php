<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aditif (aman utk data existing): klasifikasi powertrain di level MODEL.
 * Default 'ICE' untuk baris lama — koreksi manual via Filament atau
 * re-classify otomatis oleh VehicleSales import matcher (tidak pernah
 * menimpa nilai non-default tanpa aksi admin).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->table('model_vehicles', function (Blueprint $table) {
            $table->string('powertrain', 8)->default('ICE')->index()->after('name');
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->table('model_vehicles', function (Blueprint $table) {
            $table->dropIndex(['powertrain']);
            $table->dropColumn('powertrain');
        });
    }
};
