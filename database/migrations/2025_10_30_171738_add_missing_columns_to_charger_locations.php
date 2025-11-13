<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('charger_locations', function (Blueprint $table) {
            // Add columns for dual-source data management
            if (!Schema::hasColumn('charger_locations', 'data_source')) {
                $table->string('data_source')->default('community')->after('user_id'); // pln or community
            }
            if (!Schema::hasColumn('charger_locations', 'verification_status')) {
                $table->string('verification_status')->default('pending_verification')->after('data_source'); // pln_verified, community_verified, pending_verification, rejected
            }
            if (!Schema::hasColumn('charger_locations', 'master_location_id')) {
                $table->char('master_location_id', 36)->nullable()->after('verification_status'); // For consolidation
            }
            if (!Schema::hasColumn('charger_locations', 'is_master')) {
                $table->boolean('is_master')->default(true)->after('master_location_id');
            }
            if (!Schema::hasColumn('charger_locations', 'verified_by')) {
                $table->unsignedBigInteger('verified_by')->nullable()->after('is_master'); // References users.id
            }
            if (!Schema::hasColumn('charger_locations', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verified_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charger_locations', function (Blueprint $table) {
            $table->dropColumn([
                'data_source',
                'verification_status',
                'master_location_id',
                'is_master',
                'verified_by',
                'verified_at'
            ]);
        });
    }
};
