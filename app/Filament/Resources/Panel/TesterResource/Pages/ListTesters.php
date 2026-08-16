<?php

namespace App\Filament\Resources\Panel\TesterResource\Pages;

use App\Filament\Resources\Panel\TesterResource;
use App\Filament\Widgets\TesterOverviewWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTesters extends ListRecords
{
    protected static string $resource = TesterResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            TesterOverviewWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->url(route('admin.testers.export'))
                ->openUrlInNewTab(),
        ];
    }
}
