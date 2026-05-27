<?php

namespace App\Filament\Pages;

use App\Services\SpkluCsvImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ImportPlnSpklu extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static string | \UnitEnum | null $navigationGroup = 'Admin';

    protected static ?string $navigationLabel = 'Import PLN SPKLU';

    protected static ?string $title = 'Import PLN SPKLU';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.import-pln-spklu';

    public ?array $data = [];

    public ?array $lastImportSummary = null;

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Upload file CSV PLN')
                    ->description('Gunakan dua file CSV baru untuk lokasi dan detail charger. Import akan mengganti data pada pln_charger_locations dan pln_charger_location_details.')
                    ->schema([
                        FileUpload::make('locations_file')
                            ->label('File lokasi CSV')
                            ->helperText('Contoh: pln_charger_locations.csv')
                            ->disk('public')
                            ->directory('pln-spklu-imports')
                            ->acceptedFileTypes([
                                'text/csv',
                                'text/plain',
                                'application/csv',
                                'application/vnd.ms-excel',
                            ])
                            ->preserveFilenames(),
                        FileUpload::make('details_file')
                            ->label('File detail charger CSV')
                            ->helperText('Contoh: pln_charger_location_details.csv')
                            ->disk('public')
                            ->directory('pln-spklu-imports')
                            ->acceptedFileTypes([
                                'text/csv',
                                'text/plain',
                                'application/csv',
                                'application/vnd.ms-excel',
                            ])
                            ->preserveFilenames(),
                        FileUpload::make('csv_file')
                            ->label('File CSV lama')
                            ->helperText('Opsional, hanya dipakai bila dua file di atas tidak diisi.')
                            ->disk('public')
                            ->directory('pln-spklu-imports')
                            ->acceptedFileTypes([
                                'text/csv',
                                'text/plain',
                                'application/csv',
                                'application/vnd.ms-excel',
                            ])
                            ->preserveFilenames(),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Import CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Import ulang data PLN SPKLU?')
                ->modalDescription('Data lama pada pln_charger_locations dan pln_charger_location_details akan dihapus, lalu diganti dengan isi CSV ini.')
                ->action('import'),
        ];
    }

    public function import(SpkluCsvImportService $importer): void
    {
        $state = $this->form->getState();
        $locationsFilePath = $state['locations_file'] ?? null;
        $detailsFilePath = $state['details_file'] ?? null;
        $legacyFilePath = $state['csv_file'] ?? null;

        if ((! $locationsFilePath || ! $detailsFilePath) && ! $legacyFilePath) {
            Notification::make()
                ->title('File CSV belum lengkap')
                ->body('Upload file lokasi dan detail charger, atau gunakan satu file CSV lama.')
                ->danger()
                ->send();

            return;
        }

        try {
            if ($locationsFilePath && $detailsFilePath) {
                $this->lastImportSummary = $importer->importFromFiles(
                    Storage::disk('public')->path($locationsFilePath),
                    Storage::disk('public')->path($detailsFilePath),
                    replaceExisting: true,
                );
            } else {
                $this->lastImportSummary = $importer->import(
                    Storage::disk('public')->path($legacyFilePath),
                    replaceExisting: true,
                );
            }

            Notification::make()
                ->title('Import PLN SPKLU selesai')
                ->body("Lokasi: {$this->lastImportSummary['inserted_locations']}, detail charger: {$this->lastImportSummary['inserted_details']}.")
                ->success()
                ->send();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Import gagal')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }
}
