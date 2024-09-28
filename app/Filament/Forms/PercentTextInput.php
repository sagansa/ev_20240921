<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\TextInput;

class PercentTextInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->inlineLabel()
            ->minValue(0)
            ->numeric()
            ->maxValue(100)
            ->suffix('%');
    }
}
