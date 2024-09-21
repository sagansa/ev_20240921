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
        Schema::table('providers', function (Blueprint $table) {
            $table
                ->string('contact')
                ->nullable()
                ->after('user_id');
            $table
                ->string('address')
                ->nullable()
                ->after('contact');
            $table
                ->bigInteger('province_id')
                ->unsigned()
                ->after('address');
            $table
                ->bigInteger('city_id')
                ->unsigned()
                ->after('province_id');
            $table
                ->bigInteger('district_id')
                ->unsigned()
                ->after('city_id');
            $table
                ->bigInteger('subdistrict_id')
                ->unsigned()
                ->after('district_id');
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
                ->bigInteger('payment_id')
                ->unsigned()
                ->after('subdistrict_id');
            $table
                ->foreign('payment_id')
                ->references('id')
                ->on('payments')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table
                ->bigInteger('postal_code_id')
                ->unsigned()
                ->nullable()
                ->after('subdistrict_id');
            $table
                ->bigInteger('district_id')
                ->unsigned()
                ->nullable()
                ->index()
                ->change();
            $table
                ->bigInteger('subdistrict_id')
                ->unsigned()
                ->nullable()
                ->index()
                ->change();
            $table
                ->foreign('postal_code_id')
                ->references('id')
                ->on('postal_codes')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->dropColumn('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn('contact');
            $table->dropColumn('address');
            $table->dropColumn('province_id');
            $table->dropColumn('city_id');
            $table->dropColumn('district_id');
            $table->dropColumn('subdistrict_id');
            $table->dropForeign('providers_province_id_foreign');
            $table->dropForeign('providers_city_id_foreign');
            $table->dropForeign('providers_district_id_foreign');
            $table->dropForeign('providers_subdistrict_id_foreign');
            $table->dropColumn('payment_id');
            $table->dropForeign('providers_payment_id_foreign');
            $table->dropColumn('postal_code_id');
            $table
                ->bigInteger('district_id')
                ->unsigned()
                ->index()
                ->change();
            $table
                ->bigInteger('subdistrict_id')
                ->unsigned()
                ->index()
                ->change();
            $table->dropForeign('providers_postal_code_id_foreign');
            $table
                ->bigInteger('user_id')
                ->unsigned()
                ->index()
                ->after('status');
        });
    }
};
