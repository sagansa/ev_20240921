<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('type_vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table
                ->bigInteger('model_vehicle_id')
                ->unsigned()
                ->index();
            $table->decimal('battery_capacity')->nullable();
            $table->json('type_charger');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table
                ->foreign('model_vehicle_id')
                ->references('id')
                ->on('model_vehicles')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('type_vehicles');
    }
};
