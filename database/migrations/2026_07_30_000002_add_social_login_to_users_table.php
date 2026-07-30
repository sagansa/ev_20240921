<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds social-login columns for Google & Apple Sign-In.
 *
 * Runs against the `sagansa_user` connection (the DB where the `users` table lives),
 * matching App\Models\User::$connection.
 *
 * NOTE: password stays NOT NULL (existing column constraint). Social-login users get
 * a random opaque password set by the controller, so no doctrine/dbal column change is
 * needed and existing auth flows are unaffected.
 */
return new class extends Migration
{
    public function getConnection()
    {
        return config('database.default') === 'testing'
            ? config('database.default')
            : 'sagansa_user';
    }

    public function up(): void
    {
        Schema::connection($this->getConnection())->table('users', function (Blueprint $table) {
            if (! Schema::connection($this->getConnection())->hasColumn('users', 'google_id')) {
                $table->string('google_id')->nullable()->unique()->after('email');
            }
            if (! Schema::connection($this->getConnection())->hasColumn('users', 'apple_id')) {
                $table->string('apple_id')->nullable()->unique()->after('google_id');
            }
            if (! Schema::connection($this->getConnection())->hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable()->after('profile_photo_path');
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->table('users', function (Blueprint $table) {
            if (Schema::connection($this->getConnection())->hasColumn('users', 'avatar')) {
                $table->dropColumn('avatar');
            }
            if (Schema::connection($this->getConnection())->hasColumn('users', 'apple_id')) {
                $table->dropColumn('apple_id');
            }
            if (Schema::connection($this->getConnection())->hasColumn('users', 'google_id')) {
                $table->dropColumn('google_id');
            }
        });
    }
};
