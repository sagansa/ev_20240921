<?php

namespace App\Filament\Pages;

use App\Services\SpkluCsvImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ImportPlnSpklu extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationGroup = 'Admin';

    protected static ?string $navigationLabel = 'Import PLN SPKLU';

    protected static ?string $title = 'Import PLN SPKLU';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.import-pln-spklu';

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

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Upload file CSV PLN')
                    ->description('Import akan mengganti data pada pln_charger_locations dan pln_charger_location_details. Data master pendukung hanya ditambah bila belum ada.')
                    ->schema([
                        FileUpload::make('csv_file')
                            ->label('File CSV')
                            ->disk('public')
                            ->directory('pln-spklu-imports')
                            ->acceptedFileTypes([
                                'text/csv',
                                'text/plain',
                                'application/csv',
                                'application/vnd.ms-excel',
                            ])
                            ->preserveFilenames()
                            ->required(),
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
        $filePath = $state['csv_file'] ?? null;

        if (! $filePath) {
            Notification::make()
                ->title('File CSV belum dipilih')
                ->danger()
                ->send();

            return;
        }

        $fullPath = Storage::disk('public')->path($filePath);

        try {
            $this->lastImportSummary = $importer->import($fullPath, replaceExisting: true);

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
