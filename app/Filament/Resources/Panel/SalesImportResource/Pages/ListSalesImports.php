<?php

namespace App\Filament\Resources\Panel\SalesImportResource\Pages;

use App\Filament\Resources\Panel\SalesImportResource;
use App\Services\GaikindoImportService;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListSalesImports extends ListRecords
{
    protected static string $resource = SalesImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('runImport')
                ->label('Jalankan Import')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    FileUpload::make('file')
                        ->label('File xlsx GAIKINDO')
                        ->disk('local')
                        ->directory('gaikindo')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->maxSize(20480)
                        ->required(),

                    TextInput::make('year')
                        ->label('Tahun (opsional — default dari nama file)')
                        ->numeric()->minLength(4)->maxLength(4),
                ])
                ->action(function (array $data) {
                    $path = Storage::disk('local')->path($data['file']);

                    try {
                        $summary = app(GaikindoImportService::class)->importFromFile(
                            $path,
                            isset($data['year']) && $data['year'] !== '' ? (int) $data['year'] : null,
                        );
                    } catch (\Throwable $e) {
                        Notification::make()->title('Import gagal')->body($e->getMessage())->danger()->send();

                        return;
                    }

                    $coverage = $summary['coverage'] !== null ? sprintf('%.1f%%', $summary['coverage'] * 100) : 'n/a';
                    Notification::make()
                        ->title('Import berhasil')
                        ->body(sprintf(
                            '%s — %d baris model, coverage %s, %d brand & %d model baru dibuat.',
                            $summary['year'],
                            $summary['model_rows'],
                            $coverage,
                            $summary['matcher']['created_brands'],
                            $summary['matcher']['created_models'],
                        ))
                        ->success()
                        ->send();
                }),
        ];
    }
}
