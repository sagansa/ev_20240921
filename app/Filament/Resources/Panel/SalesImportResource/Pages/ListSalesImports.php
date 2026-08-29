<?php

namespace App\Filament\Resources\Panel\SalesImportResource\Pages;

use App\Filament\Resources\Panel\SalesImportResource;
use App\Services\GaikindoImportService;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
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

            Actions\Action::make('importCsv')
                ->label('Import CSV')
                ->color('success')
                ->icon('heroicon-m-arrow-up-tray')
                ->form([
                    FileUpload::make('file')
                        ->label('File CSV GAIKINDO')
                        ->disk('local')
                        ->directory('gaikindo-csv')
                        ->acceptedFileTypes([
                            'text/csv',
                            'text/plain',
                        ])
                        ->maxSize(20480)
                        ->required(),

                    TextInput::make('year')
                        ->label('Tahun periode')
                        ->numeric()
                        ->minValue(2015)
                        ->maxValue(2100)
                        ->default(now()->year)
                        ->required(),

                    Select::make('month')
                        ->label('Bulan (opsional)')
                        ->options([
                            1 => 'Jan',
                            2 => 'Feb',
                            3 => 'Mar',
                            4 => 'Apr',
                            5 => 'Mei',
                            6 => 'Jun',
                            7 => 'Jul',
                            8 => 'Agu',
                            9 => 'Sep',
                            10 => 'Okt',
                            11 => 'Nov',
                            12 => 'Des',
                        ])
                        ->placeholder('kosong = import tahunan (JAN..DEC)'),
                ])
                ->action(function (array $data) {
                    $path = Storage::disk('local')->path($data['file']);

                    try {
                        $exit = Artisan::call('vehicle-sales:import-csv', [
                            'file' => $path,
                            '--year' => (string) $data['year'],
                            ...($data['month'] ?? null ? ['--month' => (string) $data['month']] : []),
                        ]);
                        $output = Artisan::output();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Import CSV gagal')->body($e->getMessage())->danger()->send();

                        return;
                    }

                    $tail = implode("\n", array_slice(explode("\n", trim($output)), -12));

                    $notification = Notification::make()
                        ->title($exit === 0 ? 'Import CSV berhasil' : 'Import CSV gagal')
                        ->body($tail);

                    if ($exit === 0) {
                        $notification->success();
                    } else {
                        $notification->danger();
                    }

                    $notification->send();
                }),
        ];
    }
}
