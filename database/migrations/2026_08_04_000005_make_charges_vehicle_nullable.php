<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Longgarkan charges.vehicle_id ke nullable — sesi mobile SPKLU dapat dicatat
 * tanpa kendaraan terdaftar (input cepat di lapangan).
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::connection('ev')->getConnection()->getDriverName();

        if ($driver !== 'sqlite') {
            Schema::connection('ev')->table('charges', function (Blueprint $table) {
                $table->dropForeign('charges_vehicle_id_foreign');
            });
        }

        Schema::connection('ev')->table('charges', function (Blueprint $table) use ($driver) {
            $table->char('vehicle_id', 36)->nullable()->change();
            if ($driver !== 'sqlite') {
                $table->foreign('vehicle_id')
                    ->references('id')
                    ->on('vehicles')
                    ->onDelete('set null')
                    ->onUpdate('cascade');
            }
        });
    }

    public function down(): void
    {
        $driver = Schema::connection('ev')->getConnection()->getDriverName();

        if ($driver !== 'sqlite') {
            Schema::connection('ev')->table('charges', function (Blueprint $table) {
                $table->dropForeign('charges_vehicle_id_foreign');
            });
        }

        Schema::connection('ev')->table('charges', function (Blueprint $table) use ($driver) {
            $table->char('vehicle_id', 36)->nullable(false)->change();
            if ($driver !== 'sqlite') {
                $table->foreign('vehicle_id')
                    ->references('id')
                    ->on('vehicles')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
            }
        });
    }
};
