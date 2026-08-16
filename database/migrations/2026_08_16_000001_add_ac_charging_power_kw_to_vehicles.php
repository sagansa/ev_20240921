<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->table('vehicles', function (Blueprint $table): void {
            if (! Schema::connection('ev')->hasColumn('vehicles', 'ac_charging_power_kw')) {
                $table->decimal('ac_charging_power_kw', 8, 2)->nullable()->after('battery_capacity_kwh');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->table('vehicles', function (Blueprint $table): void {
            if (Schema::connection('ev')->hasColumn('vehicles', 'ac_charging_power_kw')) {
                $table->dropColumn('ac_charging_power_kw');
            }
        });
    }
};
