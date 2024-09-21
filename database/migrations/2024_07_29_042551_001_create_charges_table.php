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
        Schema::create('charges', function (Blueprint $table) {
            $table->char('id', 36)->index();
            $table->char('vehicle_id', 36)->index();
            $table->date('date');
            $table->char('charger_location_id', 36)->index();
            $table->char('charger_id', 36)->index();
            $table->bigInteger('km_now');
            $table->bigInteger('km_before');
            $table->bigInteger('start_charging_now');
            $table->bigInteger('finish_charging_now')->nullable();
            $table->bigInteger('finish_charging_before');
            $table
                ->bigInteger('parking')
                ->default(0)
                ->nullable();
            $table->decimal('kWh')->nullable();
            $table
                ->bigInteger('street_lighting_tax')
                ->default(0)
                ->nullable();
            $table
                ->bigInteger('value_added_tax')
                ->default(0)
                ->nullable();
            $table
                ->bigInteger('admin_cost')
                ->default(0)
                ->nullable();
            $table
                ->bigInteger('total_cost')
                ->default(0)
                ->nullable();
            $table->string('image', 255)->nullable();
            $table
                ->bigInteger('user_id')
                ->unsigned()
                ->index();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();

            $table
                ->foreign('vehicle_id')
                ->references('id')
                ->on('vehicles')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table
                ->foreign('charger_location_id')
                ->references('id')
                ->on('charger_locations')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table
                ->foreign('charger_id')
                ->references('id')
                ->on('chargers')
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
        Schema::dropIfExists('charges');
    }
};
