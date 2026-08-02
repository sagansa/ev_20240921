<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The scrape pipeline is a display layer, not a write path into
        // spklu_locations. Rename the "matched" column to "linked" to reflect
        // that the value is an optional reviewer reference, not an automatic
        // merge target. No foreign key is enforced: production rows may be
        // replaced by a fresh JSON import and change identity.
        Schema::connection('ev')->table('spklu_scrape_raw', function (Blueprint $table) {
            if (Schema::connection('ev')->hasColumn('spklu_scrape_raw', 'matched_spklu_location_id')) {
                $table->renameColumn('matched_spklu_location_id', 'linked_spklu_location_id');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('ev')->table('spklu_scrape_raw', function (Blueprint $table) {
            if (Schema::connection('ev')->hasColumn('spklu_scrape_raw', 'linked_spklu_location_id')) {
                $table->renameColumn('linked_spklu_location_id', 'matched_spklu_location_id');
            }
        });
    }
};
