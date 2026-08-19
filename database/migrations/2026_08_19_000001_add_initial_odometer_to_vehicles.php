<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->table('vehicles', function (Blueprint $table): void {
            if (! Schema::connection('ev')->hasColumn('vehicles', 'initial_odometer')) {
                $table->decimal('initial_odometer', 10, 2)->nullable()->default(0)->after('ac_charging_power_kw');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->table('vehicles', function (Blueprint $table): void {
            if (Schema::connection('ev')->hasColumn('vehicles', 'initial_odometer')) {
                $table->dropColumn('initial_odometer');
            }
        });
    }
};
