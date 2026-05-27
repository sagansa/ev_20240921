<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'ev';

    public function up(): void
    {
        Schema::connection('ev')->table('pln_charger_locations', function (Blueprint $table) {
            $table->unsignedBigInteger('location_category_id')->nullable()->change();
            $table->char('provider_id', 36)->nullable()->change();
            $table->unsignedBigInteger('province_id')->nullable()->change();
            $table->unsignedBigInteger('cluster_island_id')->nullable()->change();
        });

        // Drop foreign key if it exists (type mismatch: merk_charger_id is bigint, merk_chargers.id is char)
        $fkExists = DB::connection('ev')->selectOne(
            "SELECT COUNT(*) as cnt FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pln_charger_location_details'
             AND CONSTRAINT_NAME = 'pln_charger_location_details_merk_charger_id_foreign'
             AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
        );
        if ($fkExists && $fkExists->cnt > 0) {
            DB::connection('ev')->statement(
                'ALTER TABLE pln_charger_location_details DROP FOREIGN KEY pln_charger_location_details_merk_charger_id_foreign'
            );
        }

        Schema::connection('ev')->table('pln_charger_location_details', function (Blueprint $table) {
            $table->unsignedBigInteger('charger_category_id')->nullable()->change();
            $table->unsignedBigInteger('merk_charger_id')->nullable()->change();
        });

        // Drop stale index left behind
        $idxExists = DB::connection('ev')->selectOne(
            "SELECT COUNT(*) as cnt FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pln_charger_location_details'
             AND INDEX_NAME = 'pln_charger_location_details_merk_charger_id_foreign'"
        );
        if ($idxExists && $idxExists->cnt > 0) {
            DB::connection('ev')->statement(
                'ALTER TABLE pln_charger_location_details DROP INDEX pln_charger_location_details_merk_charger_id_foreign'
            );
        }
    }

    public function down(): void
    {
        // Reverting nullable is risky if data exists — leave as is
    }
};