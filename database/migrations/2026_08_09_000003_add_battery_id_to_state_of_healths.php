<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah `battery_id` ke state_of_healths — SoH menempel ke baterai (degradasi
 * baterai lama vs baru tidak bercampur). `vehicle_id` tetap dipertahankan utk
 * filter & backward-compat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->table('state_of_healths', function (Blueprint $table) {
            $table->char('battery_id', 36)->nullable()->index()->after('vehicle_id');
            $table->foreign('battery_id')
                ->references('id')->on('batteries')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->table('state_of_healths', function (Blueprint $table) {
            $table->dropForeign(['battery_id']);
            $table->dropColumn('battery_id');
        });
    }
};
