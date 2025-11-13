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
        Schema::create('location_reports', function (Blueprint $table) {
            $table->id(); // Using integer IDs to be consistent with other tables
            $table->char('location_id', 36); // References charger_locations.id
            $table->unsignedBigInteger('reporter_id'); // References users.id
            $table->enum('report_type', ['closure', 'info_update', 'charger_count', 'status_change', 'duplicate']);
            $table->text('description');
            $table->json('evidence_photos')->nullable();
            $table->enum('status', ['pending', 'in_review', 'approved', 'rejected', 'resolved'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable(); // References users.id
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Add indexes
            $table->index('location_id');
            $table->index('reporter_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_reports');
    }
};