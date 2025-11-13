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
        Schema::create('location_updates', function (Blueprint $table) {
            $table->id();
            $table->char('location_id', 36); // References charger_locations.id
            $table->unsignedBigInteger('contributor_id'); // References users.id
            $table->string('field_name');
            $table->text('old_value')->nullable();
            $table->text('new_value');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable(); // References users.id
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Add indexes
            $table->index('location_id');
            $table->index('contributor_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_updates');
    }
};