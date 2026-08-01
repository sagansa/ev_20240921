<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ev')->table('spklu_locations', function (Blueprint $table) {
            // Stores the Google Maps place identifier so scraped rows can be
            // matched against production locations and avoid duplicate inserts.
            // Not unique: legacy rows imported from JSON do not have one yet.
            $table->string('place_id')->nullable()->index()->after('external_id');
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->table('spklu_locations', function (Blueprint $table) {
            $table->dropIndex(['place_id']);
            $table->dropColumn('place_id');
        });
    }
};
