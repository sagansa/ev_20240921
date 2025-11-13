<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('charges', function (Blueprint $table) {
            if (! Schema::hasColumn('charges', 'is_finish_charging')) {
                $table->boolean('is_finish_charging')
                    ->default(false)
                    ->after('total_cost');
            }

            if (! Schema::hasColumn('charges', 'is_kwh_measured')) {
                $table->boolean('is_kwh_measured')
                    ->default(false)
                    ->after('is_finish_charging');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charges', function (Blueprint $table) {
            if (Schema::hasColumn('charges', 'is_kwh_measured')) {
                $table->dropColumn('is_kwh_measured');
            }

            if (Schema::hasColumn('charges', 'is_finish_charging')) {
                $table->dropColumn('is_finish_charging');
            }
        });
    }
};
