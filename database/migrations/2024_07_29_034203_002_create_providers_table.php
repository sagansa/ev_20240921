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
        Schema::create('providers', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('image', 255)->nullable();
            $table->string('name', 255);
            $table->tinyInteger('status');
            $table->string('contact', 255)->nullable();
            $table->string('address', 255)->nullable();
            $table
                ->bigInteger('province_id')
                ->unsigned()
                ->nullable()
                ->index();
            $table
                ->bigInteger('city_id')
                ->unsigned()
                ->nullable()
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
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

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
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('providers');
    }
};
