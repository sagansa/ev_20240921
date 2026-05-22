<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'ev';

    public function up(): void
    {
        Schema::connection('ev')->table('pln_charger_locations', function (Blueprint $table) {
            // These columns have no default but are optional when importing from CSV
            $table->unsignedBigInteger('location_category_id')->nullable()->change();
            $table->char('provider_id', 36)->nullable()->change();
            $table->unsignedBigInteger('province_id')->nullable()->change();
            $table->unsignedBigInteger('cluster_island_id')->nullable()->change();
        });

        Schema::connection('ev')->table('pln_charger_location_details', function (Blueprint $table) {
            $table->unsignedBigInteger('category_charger_id')->nullable()->change();
            $table->unsignedBigInteger('merk_charger_id')->nullable()->change();
            $table->unsignedBigInteger('charging_type_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Reverting nullable is risky if data exists — leave as is
    }
};
