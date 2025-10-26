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
        Schema::create('pln_charger_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->char('provider_id', 36);
            $table->string('owner_machine');
            $table->decimal('latitude', 12, 8);
            $table->decimal('longitude', 12, 8);
            $table->unsignedBigInteger('location_category_id');
            $table->unsignedBigInteger('cluster_island_id');
            $table->unsignedBigInteger('province_id');
            $table->timestamps();

            $table->foreign('provider_id')
                ->references('id')
                ->on('providers');

            $table->foreign('location_category_id')
                ->references('id')
                ->on('location_categories');

            $table->foreign('cluster_island_id')
                ->references('id')
                ->on('cluster_islands');

            $table->foreign('province_id')
                ->references('id')
                ->on('provinces');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pln_charger_locations');
    }
};
