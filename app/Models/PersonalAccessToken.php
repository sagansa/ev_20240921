<?php

namespace App\Models;

use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * Custom Sanctum token model.
 *
 * Why this exists: in this project the User model lives on the
 * `sagansa_user` database connection, while `config/database.default` is `ev`.
 * Sanctum's default behaviour writes tokens on the owner's connection
 * (`sagansa_user`) but reads them on the default connection (`ev`) during
 * request authentication — so freshly issued tokens are never found and every
 * bearer-authenticated request returns 401.
 *
 * Forcing this model to the same connection as the User model makes the
 * write and the read land on the same database.
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $connection;

    public function __construct(array $attributes = [])
    {
        $this->connection = Config::get('database.default') === 'testing'
            ? Config::get('database.default')
            : 'sagansa_user';

        parent::__construct($attributes);
    }
}
