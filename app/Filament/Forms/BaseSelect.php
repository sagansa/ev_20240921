<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class BaseSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->inlineLabel()
            ->required();
    }
}
