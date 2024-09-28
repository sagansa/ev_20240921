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
            ->currency('IDR')
            ->summarize(Sum::make()
                ->numeric(
                    thousandsSeparator: '.'
                )
                ->label('')
                ->currency('IDR')

            );
    }
}
