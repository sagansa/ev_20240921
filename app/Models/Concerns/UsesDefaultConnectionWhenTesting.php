<?php

namespace App\Models\Concerns;

trait UsesDefaultConnectionWhenTesting
{
    public function getConnectionName()
    {
        if (app()->environment('testing')) {
            return config('database.default');
        }

        return parent::getConnectionName();
    }
}

