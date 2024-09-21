<?php

namespace App\Tables\Columns;

use Filament\Tables\Columns\TextColumn;

class StatusActiveColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->formatStateUsing(
            fn(string $state): string => match ($state) {
                '1' => 'active',
                '2' => 'inactive',
                default => $state,
            }
        );

        $this->badge()
            ->color(
            fn(string $state): string => match ($state) {
                '1' => 'success',
                '2' => 'danger',
                default => 'gray',
            }
        );
    }
}
