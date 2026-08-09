<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah `battery_id` ke charges — sesi charging di-link ke baterai aktif
 * kendaraan saat dibuat (auto-assign). Nullable karena sesi boleh tanpa
 * vehicle (input cepat mobile) / baterai belum diketahui.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->table('charges', function (Blueprint $table) {
            $table->char('battery_id', 36)->nullable()->index()->after('vehicle_id');
            $table->foreign('battery_id')
                ->references('id')->on('batteries')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->table('charges', function (Blueprint $table) {
            $table->dropForeign(['battery_id']);
            $table->dropColumn('battery_id');
        });
    }
};
