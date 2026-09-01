<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Powertrain pada level TYPE — varian/type adalah pemilik powertrain yang
 * sesungguhnya (satu keluarga model bisa punya varian G, HEV, bahkan BEV).
 * model_vehicles.powertrain tetap ada sebagai "dominan" demi kompatibilitas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->table('type_vehicles', function (Blueprint $table) {
            $table->string('powertrain', 8)->nullable()->index()->after('name');
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->table('type_vehicles', function (Blueprint $table) {
            $table->dropIndex(['powertrain']);
            $table->dropColumn('powertrain');
        });
    }
};
