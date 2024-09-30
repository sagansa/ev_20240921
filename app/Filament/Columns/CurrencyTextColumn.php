<?php

namespace App\Filament\Columns;

use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;

class CurrencyTextColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->sortable()
            ->prefix('Rp ')
            ->numeric(
                    thousandsSeparator: '.'
                )
            ->summarize(Sum::make()
                ->numeric(
                    thousandsSeparator: '.'
                )
                ->label('')
                ->prefix('Rp ')

            );
    }
}
