<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * raw_gabungan_key tidak lagi unique: master CONNECTING sengaja memuat
 * kembar ejaan dari laporan GAIKINDO ("ZY-HR" vs "ZY - HR", "CRV" vs
 * "CR-V", "(New)" vs "(New )") yang squash-nya identik. Membatasi satu
 * key per baris membuat kembar kedua selamanya tanpa key. Pencocokan
 * pakai ->first(), jadi duplikat key aman.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_connectings', function (Blueprint $table) {
            $table->dropUnique('vehicle_connectings_raw_gabungan_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_connectings', function (Blueprint $table) {
            $table->unique('raw_gabungan_key');
        });
    }
};
