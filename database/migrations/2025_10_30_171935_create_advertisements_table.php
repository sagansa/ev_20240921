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
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('target_url')->nullable();
            $table->enum('platform', ['mobile', 'web', 'both'])->default('both');
            $table->enum('position', ['banner', 'interstitial', 'native'])->default('banner');
            $table->boolean('is_active')->default(true);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('impression_count')->default(0);
            $table->integer('click_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            // Add indexes
            $table->index('is_active');
            $table->index('platform');
            $table->index('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};