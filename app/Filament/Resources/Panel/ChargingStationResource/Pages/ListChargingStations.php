<?php

namespace App\Filament\Resources\Panel\ChargingStationResource\Pages;

use App\Filament\Resources\Panel\ChargingStationResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;

class ListChargingStations extends ListRecords
{
    protected static string $resource = ChargingStationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('rehydrate')
                ->label('Rehydrate dari ESDM')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->requiresConfirmation()
                ->modalDescription('Jalankan esdm:hydrate-canonical — roll-up ulang data canonical dari ESDM. Idempoten dan aman dijalankan berulang.')
                ->action(function (): void {
                    Artisan::call('esdm:hydrate-canonical');

                    Notification::make()
                        ->title('Rehydrate selesai')
                        ->body('Stasiun canonical diperbarui dari ESDM.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
