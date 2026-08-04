<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Longgarkan field NOT NULL di charges agar input cepat mobile (tanpa harus
 * isi semua field km/battery/date/charger) tidak gagal constraint.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::connection('ev')->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            Schema::connection('ev')->table('charges', function (Blueprint $t) {
                $t->date('date')->nullable()->change();
                $t->char('charger_location_id', 36)->nullable()->change();
                $t->char('charger_id', 36)->nullable()->change();
                $t->bigInteger('km_now')->nullable()->change();
                $t->bigInteger('km_before')->nullable()->change();
                $t->bigInteger('start_charging_now')->nullable()->change();
                $t->boolean('is_finish_charging')->nullable()->change();
                $t->bigInteger('finish_charging_before')->nullable()->change();
                $t->boolean('is_kwh_measured')->nullable()->change();
            });
            return;
        }

        // 1. Drop & recreate FK untuk charger_location_id, charger_id (SET NULL).
        foreach (['charger_location_id' => 'charger_locations', 'charger_id' => 'chargers'] as $col => $refTable) {
            $fkName = "charges_{$col}_foreign";
            $exists = DB::connection('ev')->select(
                "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'charges'
                   AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL",
                [$col]
            );
            if (! empty($exists)) {
                Schema::connection('ev')->table('charges', fn (Blueprint $t) => $t->dropForeign($fkName));
            }
        }

        // 2. Longgarkan kolom NOT NULL → nullable (raw ALTER untuk hindari DBAL).
        $nullableCols = [
            'date' => 'date',
            'charger_location_id' => 'char(36)',
            'charger_id' => 'char(36)',
            'km_now' => 'bigint',
            'km_before' => 'bigint',
            'start_charging_now' => 'bigint',
            'is_finish_charging' => 'tinyint(1)',
            'finish_charging_before' => 'bigint',
            'is_kwh_measured' => 'tinyint(1)',
        ];
        foreach ($nullableCols as $col => $type) {
            DB::connection('ev')->statement("ALTER TABLE `charges` MODIFY `{$col}` {$type} NULL");
        }

        // 3. Recreate FK dgn SET NULL.
        Schema::connection('ev')->table('charges', function (Blueprint $t) {
            $t->foreign('charger_location_id')
                ->references('id')->on('charger_locations')
                ->onDelete('set null')->onUpdate('cascade');
            $t->foreign('charger_id')
                ->references('id')->on('chargers')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        $driver = Schema::connection('ev')->getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            return;
        }

        Schema::connection('ev')->table('charges', function (Blueprint $t) {
            $t->dropForeign('charges_charger_location_id_foreign');
            $t->dropForeign('charges_charger_id_foreign');
        });

        $restoreCols = [
            'date' => 'date',
            'charger_location_id' => 'char(36)',
            'charger_id' => 'char(36)',
            'km_now' => 'bigint',
            'km_before' => 'bigint',
            'start_charging_now' => 'bigint',
            'is_finish_charging' => 'tinyint(1)',
            'finish_charging_before' => 'bigint',
            'is_kwh_measured' => 'tinyint(1)',
        ];
        foreach ($restoreCols as $col => $type) {
            DB::connection('ev')->statement("ALTER TABLE `charges` MODIFY `{$col}` {$type} NOT NULL");
        }

        Schema::connection('ev')->table('charges', function (Blueprint $t) {
            $t->foreign('charger_location_id')
                ->references('id')->on('charger_locations')
                ->onDelete('cascade')->onUpdate('cascade');
            $t->foreign('charger_id')
                ->references('id')->on('chargers')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }
};
