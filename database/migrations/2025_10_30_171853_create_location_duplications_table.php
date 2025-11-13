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
        Schema::create('location_duplications', function (Blueprint $table) {
            $table->id();
            $table->char('primary_location_id', 36); // References charger_locations.id
            $table->char('duplicate_location_id', 36); // References charger_locations.id
            $table->decimal('distance_meters', 8, 2)->nullable();
            $table->decimal('similarity_score', 5, 2)->default(0); // 0-100 percentage
            $table->enum('status', ['detected', 'confirmed', 'resolved', 'false_positive'])->default('detected');
            $table->unsignedBigInteger('resolved_by')->nullable(); // References users.id
            $table->timestamp('resolved_at')->nullable();
            $table->enum('resolution_action', ['merge', 'keep_separate', 'delete_duplicate'])->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Add indexes
            $table->index('primary_location_id');
            $table->index('duplicate_location_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_duplications');
    }
};