<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('youtube_collections', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('title');
            $table->string('video_id');
            $table->text('description')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('channel_name')->nullable();
            $table->string('category')->nullable();
            $table->integer('view_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'is_active']);
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('youtube_collections');
    }
};