<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kunci squash (huruf+angka saja, uppercase) dari raw_gabungan — identitas
 * unik yang kebal perbedaan spasi/kapitalisasi/tanda baca. "HINO ZY - HR"
 * dan "ZY-HR" menjadi baris yang sama; duplikat logis tidak mungkin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->table('vehicle_connectings', function (Blueprint $table) {
            $table->string('raw_gabungan_key')->nullable()->after('raw_gabungan');
        });

        // Backfill kunci dari raw existing.
        $rows = DB::connection('ev')->table('vehicle_connectings')->select('id', 'raw_gabungan')->get();
        foreach ($rows as $row) {
            DB::connection('ev')->table('vehicle_connectings')
                ->where('id', $row->id)
                ->update(['raw_gabungan_key' => preg_replace('/[^A-Z0-9]/u', '', mb_strtoupper(preg_replace('/\s+/', ' ', trim($row->raw_gabungan))))]);
        }

        Schema::connection('ev')->table('vehicle_connectings', function (Blueprint $table) {
            $table->unique('raw_gabungan_key');
            $table->index('raw_gabungan_key');
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->table('vehicle_connectings', function (Blueprint $table) {
            $table->dropUnique(['raw_gabungan_key']);
            $table->dropColumn('raw_gabungan_key');
        });
    }
};
