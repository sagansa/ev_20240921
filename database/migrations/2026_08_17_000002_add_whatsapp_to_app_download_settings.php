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
        if (Schema::connection('ev')->hasTable('app_download_settings')) {
            Schema::connection('ev')->table('app_download_settings', function (Blueprint $table) {
                if (! Schema::connection('ev')->hasColumn('app_download_settings', 'whatsapp_number')) {
                    $table->string('whatsapp_number')->nullable()->default('08111923572');
                }
                if (! Schema::connection('ev')->hasColumn('app_download_settings', 'whatsapp_text')) {
                    $table->string('whatsapp_text')->nullable()->default('Halo Admin EV Charge ID, saya ingin bertanya mengenai kerjasama / bantuan aplikasi.');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::connection('ev')->hasTable('app_download_settings')) {
            Schema::connection('ev')->table('app_download_settings', function (Blueprint $table) {
                if (Schema::connection('ev')->hasColumn('app_download_settings', 'whatsapp_number')) {
                    $table->dropColumn(['whatsapp_number', 'whatsapp_text']);
                }
            });
        }
    }
};
