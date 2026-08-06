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
            if (! Schema::connection('ev')->hasColumn('charging_stations', 'toll_category')) {
                $table->string('toll_category', 32)->nullable()->after('provinsi')->index();
            }
            if (! Schema::connection('ev')->hasColumn('charging_stations', 'location_category')) {
                $table->string('location_category', 64)->nullable()->after('toll_category')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->table('charging_stations', function (Blueprint $table) {
            if (Schema::connection('ev')->hasColumn('charging_stations', 'toll_category')) {
                $table->dropColumn('toll_category');
            }
            if (Schema::connection('ev')->hasColumn('charging_stations', 'location_category')) {
                $table->dropColumn('location_category');
            }
        });
    }
};
