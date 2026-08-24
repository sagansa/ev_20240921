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
        Schema::connection('ev')->table('app_download_settings', function (Blueprint $table) {
            $table->boolean('android_pre_release_flow')->default(false)->after('android_notes');
            $table->string('support_email')->nullable()->after('android_pre_release_flow');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('ev')->table('app_download_settings', function (Blueprint $table) {
            $table->dropColumn(['android_pre_release_flow', 'support_email']);
        });
    }
};
