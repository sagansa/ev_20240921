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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->char('id', 36)->index();
            $table->string('image', 255)->nullable();
            $table->string('license_plate', 255)->nullable();
            $table
                ->bigInteger('brand_vehicle_id')
                ->unsigned()
                ->index();
            $table
                ->bigInteger('model_vehicle_id')
                ->unsigned()
                ->index();
            $table
                ->bigInteger('type_vehicle_id')
                ->unsigned()
                ->nullable()
                ->index();
            $table->date('ownership')->nullable();
            $table->tinyInteger('status');
            $table
                ->bigInteger('user_id')
                ->unsigned()
                ->index();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();

            $table
                ->foreign('brand_vehicle_id')
                ->references('id')
                ->on('brand_vehicles')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table
                ->foreign('model_vehicle_id')
                ->references('id')
                ->on('model_vehicles')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table
                ->foreign('type_vehicle_id')
                ->references('id')
                ->on('type_vehicles')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table
                ->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->primary('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vehicles');
    }
};
