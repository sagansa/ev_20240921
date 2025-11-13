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
        Schema::create('contributor_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // References users.id
            $table->integer('credibility_score')->default(0);
            $table->integer('total_contributions')->default(0);
            $table->integer('approved_contributions')->default(0);
            $table->integer('rejected_contributions')->default(0);
            $table->json('badges')->nullable();
            $table->boolean('is_trusted')->default(false);
            $table->enum('trust_level', ['novice', 'contributor', 'trusted', 'expert'])->default('novice');
            $table->timestamp('last_contribution_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Add unique constraint
            $table->unique('user_id');
            
            // Add indexes
            $table->index('user_id');
            $table->index('credibility_score');
            $table->index('is_trusted');
            $table->index('trust_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contributor_profiles');
    }
};