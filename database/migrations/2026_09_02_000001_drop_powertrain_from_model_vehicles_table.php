<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['ev', config('database.default')] as $conn) {
            try {
                if (! Schema::connection($conn)->hasTable('model_vehicles')) {
                    continue;
                }
                if (! Schema::connection($conn)->hasColumn('model_vehicles', 'powertrain')) {
                    continue;
                }
                Schema::connection($conn)->table('model_vehicles', function (Blueprint $table) {
                    try {
                        $table->dropIndex(['powertrain']);
                    } catch (\Throwable $e) {
                        // index may not exist on sqlite
                    }
                    $table->dropColumn('powertrain');
                });
            } catch (\Throwable $e) {
                // ignore if connection not configured or table missing
            }
        }
    }

    public function down(): void
    {
        Schema::connection('ev')->table('model_vehicles', function (Blueprint $table) {
            $table->string('powertrain', 8)->default('ICE')->index()->after('name');
        });
    }
};
