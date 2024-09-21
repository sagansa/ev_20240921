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
        Schema::create('discount_home_chargings', function (Blueprint $table) {
            $table->id();
            $table->char('charger_location_id');
            $table->date('month');
            $table->decimal('total_kwh');
            $table->decimal('discount_kwh');
            $table->bigInteger('discount_total');
            $table->bigInteger('user_id')->unsigned();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table
                ->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table
                ->foreign('charger_location_id')
                ->references('id')
                ->on('charger_locations')
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
        Schema::dropIfExists('discount_home_chargings');
    }
};
