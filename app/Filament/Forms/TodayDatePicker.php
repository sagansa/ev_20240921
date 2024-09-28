<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\DatePicker;

class TodayDatePicker extends DatePicker
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->rules(['date'])
            ->default(today())
            ->required();
    }
}
