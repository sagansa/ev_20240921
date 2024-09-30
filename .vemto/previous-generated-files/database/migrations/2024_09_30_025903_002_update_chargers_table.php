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
        Schema::table('chargers', function (Blueprint $table) {
            $table
                ->bigInteger('merk_charger_id')
                ->unsigned()
                ->after('unit');
            $table
                ->foreign('merk_charger_id')
                ->references('id')
                ->on('merk_chargers')
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
        Schema::table('chargers', function (Blueprint $table) {
            $table->dropColumn('merk_charger_id');
            $table->dropForeign('chargers_merk_charger_id_foreign');
        });
    }
};
