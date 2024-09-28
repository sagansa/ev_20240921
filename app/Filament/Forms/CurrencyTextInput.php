<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\TextInput;

class CurrencyTextInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->prefix('Rp')
            ->inlineLabel()
            ->required()
            ->numeric()
            ->minValue(0)
            ->default(0)
            ->currencyMask(thousandSeparator: '.',decimalSeparator: ',',precision: 0);
    }
}
