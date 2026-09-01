<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nama parsed dari CSV (sumber kebenaran teks) — memungkinkan
 * applyToCatalog membuat brand/model/type BARU dari connecting meski
 * link id-nya masih NULL, dan menjaga teks tetap tersimpan walau entitas
 * katalog di-rename.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->table('vehicle_connectings', function (Blueprint $table) {
            $table->string('brand_name')->nullable()->after('fuel');
            $table->string('model_name')->nullable()->after('brand_name');
            $table->string('type_name')->nullable()->after('model_name');
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->table('vehicle_connectings', function (Blueprint $table) {
            $table->dropColumn(['brand_name', 'model_name', 'type_name']);
        });
    }
};
