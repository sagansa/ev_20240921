<?php

namespace App\Tables\Columns;

use Filament\Tables\Columns\TextColumn;

class LocationOnColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->formatStateUsing(
            fn(string $state): string => match ($state) {
                '1' => 'public',
                '2' => 'private',
                '3' => 'dealer',
                '4' => 'closed',
                default => $state,
            }
        );

        $this->badge()
            ->color(
                fn(string $state): string => match ($state) {
                    '1' => 'success',
                    '2' => 'warning',
                    '3' => 'gray',
                    '3' => 'closed',
                    default => 'gray',
                }
            );
    }
}
