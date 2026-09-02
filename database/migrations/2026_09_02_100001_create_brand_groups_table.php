<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Klaster brand ke induk perusahaan (level grup industri, BUKAN merge brand):
 * mis. SAIC → MG + Wuling + Maxus. Brand tetap entitas terpisah; penggabungan
 * hanya terjadi saat agregasi (leaderboard grup, badge katalog). Kolom di
 * brand_vehicles nullable — brand tanpa grup tetap berdiri sendiri.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->create('brand_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        Schema::connection('ev')->table('brand_vehicles', function (Blueprint $table) {
            $table->unsignedBigInteger('brand_group_id')->nullable()->index()->after('name');
            $table->foreign('brand_group_id')->references('id')->on('brand_groups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->table('brand_vehicles', function (Blueprint $table) {
            $table->dropForeign(['brand_group_id']);
            $table->dropIndex(['brand_group_id']);
            $table->dropColumn('brand_group_id');
        });
        Schema::connection('ev')->dropIfExists('brand_groups');
    }
};
