<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Longgarkan FK region (province_id/city_id/provider_id) di charger_locations
 * agar lokasi custom/home milik user bisa dibuat tanpa harus punya relasi wilayah
 * ataupun provider (reverse-geocode bisa gagal / user tidak memilih provider).
 * Tambah kolom denormalized province_name/city_name utk display yang robust.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::connection('ev')->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            Schema::connection('ev')->table('charger_locations', function (Blueprint $t) {
                $t->char('provider_id', 36)->nullable()->change();
                $t->bigInteger('province_id')->nullable()->change();
                $t->bigInteger('city_id')->nullable()->change();
                $t->string('province_name', 255)->nullable()->after('city_id');
                $t->string('city_name', 255)->nullable()->after('province_name');
            });
            return;
        }

        // 1. Drop FK yg menghalangi ALTER kolom jadi nullable.
        foreach (['provider_id' => 'providers', 'province_id' => 'provinces', 'city_id' => 'cities'] as $col => $refTable) {
            $fkName = "charger_locations_{$col}_foreign";
            $exists = DB::connection('ev')->select(
                "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'charger_locations'
                   AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL",
                [$col]
            );
            if (! empty($exists)) {
                Schema::connection('ev')->table('charger_locations', fn (Blueprint $t) => $t->dropForeign($fkName));
            }
        }

        // 2. Longgarkan kolom (raw ALTER utk hindari DBAL).
        $nullableCols = [
            'provider_id' => 'char(36)',
            'province_id' => 'bigint unsigned',
            'city_id' => 'bigint unsigned',
        ];
        foreach ($nullableCols as $col => $type) {
            DB::connection('ev')->statement("ALTER TABLE `charger_locations` MODIFY `{$col}` {$type} NULL");
        }

        // 3. Kolom display denormalized.
        Schema::connection('ev')->table('charger_locations', function (Blueprint $t) {
            $t->string('province_name', 255)->nullable()->after('city_id');
            $t->string('city_name', 255)->nullable()->after('province_name');
        });

        // 4. Recreate FK.
        Schema::connection('ev')->table('charger_locations', function (Blueprint $t) {
            $t->foreign('provider_id')->references('id')->on('providers')
                ->onDelete('set null')->onUpdate('cascade');
            $t->foreign('province_id')->references('id')->on('provinces')
                ->onDelete('set null')->onUpdate('cascade');
            $t->foreign('city_id')->references('id')->on('cities')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        $driver = Schema::connection('ev')->getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            return;
        }

        Schema::connection('ev')->table('charger_locations', function (Blueprint $t) {
            $t->dropForeign('charger_locations_provider_id_foreign');
            $t->dropForeign('charger_locations_province_id_foreign');
            $t->dropForeign('charger_locations_city_id_foreign');
            $t->dropColumn(['province_name', 'city_name']);
        });

        $restoreCols = [
            'provider_id' => 'char(36)',
            'province_id' => 'bigint unsigned',
            'city_id' => 'bigint unsigned',
        ];
        foreach ($restoreCols as $col => $type) {
            DB::connection('ev')->statement("ALTER TABLE `charger_locations` MODIFY `{$col}` {$type} NOT NULL");
        }

        Schema::connection('ev')->table('charger_locations', function (Blueprint $t) {
            $t->foreign('provider_id')->references('id')->on('providers')
                ->onDelete('cascade')->onUpdate('cascade');
            $t->foreign('province_id')->references('id')->on('provinces')
                ->onDelete('cascade')->onUpdate('cascade');
            $t->foreign('city_id')->references('id')->on('cities')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }
};
