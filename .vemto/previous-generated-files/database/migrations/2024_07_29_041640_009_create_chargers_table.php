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
        Schema::create('chargers', function (Blueprint $table) {
            $table->char('id', 36)->index();
            $table->char('charger_location_id', 36)->index();
            $table
                ->bigInteger('current_charger_id')
                ->unsigned()
                ->index();
            $table
                ->bigInteger('type_charger_id')
                ->unsigned()
                ->index();
            $table
                ->bigInteger('power_charger_id')
                ->unsigned()
                ->index();
            $table->tinyInteger('unit')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();

            $table
                ->foreign('current_charger_id')
                ->references('id')
                ->on('current_chargers')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table
                ->foreign('type_charger_id')
                ->references('id')
                ->on('type_chargers')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table
                ->foreign('power_charger_id')
                ->references('id')
                ->on('power_chargers')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table
                ->foreign('charger_location_id')
                ->references('id')
                ->on('charger_locations')
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
        Schema::dropIfExists('chargers');
    }
};
