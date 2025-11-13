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
        Schema::create('location_audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('location_id', 36); // References charger_locations.id
            $table->unsignedBigInteger('user_id')->nullable(); // References users.id (nullable for system actions)
            $table->enum('action', ['create', 'update', 'delete', 'verify', 'reject', 'merge', 'report']);
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->text('notes')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Add indexes
            $table->index('location_id');
            $table->index('user_id');
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_audit_logs');
    }
};
