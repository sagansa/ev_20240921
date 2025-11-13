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
        Schema::create('visitor_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('session_id');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('platform')->nullable();
            $table->json('location_accessed')->nullable(); // Array of location IDs accessed
            $table->integer('visit_duration')->nullable(); // In seconds
            $table->integer('pages_viewed')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            // Add indexes
            $table->index('session_id');
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_profiles');
    }
};