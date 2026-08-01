<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('sagansa_user')->table('users', function (Blueprint $table) {
            if (! Schema::connection('sagansa_user')->hasColumn('users', 'google_id')) {
                $table->string('google_id')->nullable()->unique()->after('email');
            }
            if (! Schema::connection('sagansa_user')->hasColumn('users', 'apple_id')) {
                $table->string('apple_id')->nullable()->unique()->after('google_id');
            }
            if (! Schema::connection('sagansa_user')->hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable()->after('profile_photo_path');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('sagansa_user')->table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'apple_id', 'avatar']);
        });
    }
};
