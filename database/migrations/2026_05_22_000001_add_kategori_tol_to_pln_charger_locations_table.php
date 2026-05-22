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
            if (! Schema::connection('ev')->hasColumn('pln_charger_locations', 'kategori_tol')) {
                $table->string('kategori_tol')->nullable()->after('location_category_id');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->table('pln_charger_locations', function (Blueprint $table) {
            if (Schema::connection('ev')->hasColumn('pln_charger_locations', 'kategori_tol')) {
                $table->dropColumn('kategori_tol');
            }
        });
    }
};
