<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'ev';

    public function up(): void
    {
        Schema::connection('ev')->table('charging_stations', function (Blueprint $table) {
            if (! Schema::connection('ev')->hasColumn('charging_stations', 'kategori_tol')) {
                $table->string('kategori_tol', 32)->nullable()->after('provinsi')->index();
            }
            if (! Schema::connection('ev')->hasColumn('charging_stations', 'kategori_lokasi')) {
                $table->string('kategori_lokasi', 64)->nullable()->after('kategori_tol')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->table('charging_stations', function (Blueprint $table) {
            if (Schema::connection('ev')->hasColumn('charging_stations', 'kategori_tol')) {
                $table->dropColumn('kategori_tol');
            }
            if (Schema::connection('ev')->hasColumn('charging_stations', 'kategori_lokasi')) {
                $table->dropColumn('kategori_lokasi');
            }
        });
    }
};
