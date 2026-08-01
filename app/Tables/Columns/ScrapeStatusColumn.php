<?php

namespace App\Tables\Columns;

use Filament\Tables\Columns\TextColumn;

class ScrapeStatusColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->formatStateUsing(
            fn (string $state): string => match ($state) {
                '0' => 'new',
                '1' => 'duplicate',
                '2' => 'approved',
                '3' => 'rejected',
                default => $state,
            }
        );

        $this->badge()
            ->color(
                fn (string $state): string => match ($state) {
                    '0' => 'warning',
                    '1' => 'info',
                    '2' => 'success',
                    '3' => 'danger',
                    default => 'gray',
                }
            );
    }
}
