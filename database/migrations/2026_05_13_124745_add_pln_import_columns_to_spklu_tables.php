<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'ev';

    public function up(): void
    {
        // Tambah pln_id ke pln_charger_locations
        Schema::connection('ev')->table('pln_charger_locations', function (Blueprint $table) {
            if (!Schema::connection('ev')->hasColumn('pln_charger_locations', 'pln_id')) {
                $table->unsignedInteger('pln_id')->nullable()->unique()->after('id')
                      ->comment('ID Spklu dari CSV PLN');
            }
        });

        // Tambah chargebox_id & chargebox_name ke pln_charger_location_details
        Schema::connection('ev')->table('pln_charger_location_details', function (Blueprint $table) {
            if (!Schema::connection('ev')->hasColumn('pln_charger_location_details', 'chargebox_id')) {
                $table->string('chargebox_id')->nullable()->after('id')
                      ->comment('Chargebox ID dari CSV PLN');
            }
            if (!Schema::connection('ev')->hasColumn('pln_charger_location_details', 'chargebox_name')) {
                $table->string('chargebox_name')->nullable()->after('chargebox_id')
                      ->comment('Nama Chargebox dari CSV PLN');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->table('pln_charger_locations', function (Blueprint $table) {
            $table->dropColumn('pln_id');
        });

        Schema::connection('ev')->table('pln_charger_location_details', function (Blueprint $table) {
            $table->dropColumn(['chargebox_id', 'chargebox_name']);
        });
    }
};
