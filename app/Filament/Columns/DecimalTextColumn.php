<?php

namespace App\Filament\Columns;

use Filament\Tables\Columns\TextColumn;

class DecimalTextColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->numeric(
                decimalPlaces: 3,
                thousandsSeparator: '.',
                decimalSeparator: ',',
            );
    }
}
