<?php

namespace App\Tables\Columns;

use Filament\Tables\Columns\TextColumn;

class StatusLocationColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->formatStateUsing(
            fn(string $state): string => match ($state) {
                '1' => 'not verified',
                '2' => 'verified',
                '3' => 'closed',
                '4' => 'external',
            }
        );

        $this->badge()
            ->color(
                fn(string $state): string => match ($state) {
                    '1' => 'warning',
                    '2' => 'success',
                    '3' => 'danger',
                    '4' => 'info',
                }
            );
    }
}
