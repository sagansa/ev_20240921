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
        Schema::create('pln_charger_location_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pln_charger_location_id');
            $table->string('power');
            $table->string('is_active_charger');
            $table->integer('count_connector_charger');
            $table->date('operation_date');
            $table->year('year');
            $table->unsignedBigInteger('charger_category_id');
            $table->char('merk_charger_id', 36);
            $table->timestamps();

            $table->foreign('pln_charger_location_id')
                ->references('id')
                ->on('pln_charger_locations');

            $table->foreign('charger_category_id')
                ->references('id')
                ->on('charger_categories');

            $table->foreign('merk_charger_id')
                ->references('id')
                ->on('merk_chargers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pln_charger_location_details');
    }
};
