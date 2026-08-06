<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Align type_chargers.name dgn produksi: VARCHAR(255) menggantikan enum('AC','DC').
 *
 * Produksi sudah menyimpan NAMA KONEKTOR (CCS2, Type 2, Chademo, AC GBT, DC GBT,
 * dst.) — bukan nilai enum AC/DC. Klasifikasi AC/DC diturunkan dari nama
 * konektor via Charge::resolveConnectorToAcDc(). Migration source (enum) sudah
 * out-of-sync dgn DB produksi (varchar) sejak lama; ini menyamakan skema test
 * (SQLite) agar konsisten, sekaligus mendokumentasikan kebenaran produksi.
 *
 * Idempoten: skip bila kolom sudah bukan enum (CHANGE COLUMN pada MySQL atau
 * no-op bila sudah string di SQLite — SQLite tidak enforce type secara ketat).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->table('type_chargers', function (Blueprint $table) {
            // change() → string(255) di MySQL; di SQLite (test) tidak ada efek
            // type-check yg ketat, tapi tetap aman dipanggil.
            $table->string('name', 255)->change();
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->table('type_chargers', function (Blueprint $table) {
            $table->enum('name', ['AC', 'DC'])->change();
        });
    }
};
