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
        Schema::create('charger_locations', function (Blueprint $table) {
            $table->char('id', 36)->index();
            $table->string('image', 255)->nullable();
            $table->string('name', 255);
            $table
                ->char('provider_id', 36)
                ->unsigned()
                ->index();
            $table->tinyInteger('location_on');
            $table->tinyInteger('status');
            $table->text('description')->nullable();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->boolean('parking')->nullable();
            $table->string('address', 255)->nullable();
            $table
                ->bigInteger('province_id')
                ->unsigned()
                ->index();
            $table
                ->bigInteger('city_id')
                ->unsigned()
                ->index();
            $table
                ->bigInteger('district_id')
                ->unsigned()
                ->nullable()
                ->index();
            $table
                ->bigInteger('subdistrict_id')
                ->unsigned()
                ->nullable()
                ->index();
            $table
                ->bigInteger('postal_code_id')
                ->unsigned()
                ->nullable()
                ->index();
            $table
                ->bigInteger('user_id')
                ->unsigned()
                ->index();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();

            $table
                ->foreign('provider_id')
                ->references('id')
                ->on('providers')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table
                ->foreign('province_id')
                ->references('id')
                ->on('provinces')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table
                ->foreign('city_id')
                ->references('id')
                ->on('cities')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table
                ->foreign('district_id')
                ->references('id')
                ->on('districts')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table
                ->foreign('subdistrict_id')
                ->references('id')
                ->on('subdistricts')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table
                ->foreign('postal_code_id')
                ->references('id')
                ->on('postal_codes')
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
        Schema::dropIfExists('charger_locations');
    }
};
