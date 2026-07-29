<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->table('spklu_locations', function (Blueprint $table) {
            $table->char('provider_id', 36)->nullable()->after('external_id')->index();
            $table->foreign('provider_id')->references('id')->on('providers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->table('spklu_locations', function (Blueprint $table) {
            $table->dropForeign(['provider_id']);
            $table->dropColumn('provider_id');
        });
    }
};
