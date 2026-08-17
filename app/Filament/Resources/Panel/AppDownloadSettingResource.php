<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Resources\Panel\AppDownloadSettingResource\Pages;
use App\Models\AppDownloadSetting;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class AppDownloadSettingResource extends Resource
{
    protected static ?string $model = AppDownloadSetting::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static string | \UnitEnum | null $navigationGroup = 'Admin';

    protected static ?string $navigationLabel = 'Mobile App Links & Popup';

    protected static ?string $modelLabel = 'Pengaturan Download App';

    protected static ?string $pluralModelLabel = 'Pengaturan Download App';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole('super_admin') || Auth::user()?->hasRole('admin') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Status & Pengaturan Utama Popup')
                    ->description('Atur apakah popup wajib muncul di halaman utama dan apakah dapat ditutup oleh pengunjung.')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Aktifkan Popup Download di Halaman Utama')
                            ->helperText('Jika aktif, pengunjung akan langsung disajikan popup download aplikasi.')
                            ->default(true),
                        Toggle::make('is_closable')
                            ->label('Izinkan Pengunjung Menutup Popup')
                            ->helperText('Matikan opsi ini jika ingin popup bersifat wajib (gate) tanpa tombol close.')
                            ->default(false),
                        TextInput::make('badge_text')
                            ->label('Teks Label / Badge')
                            ->default('Official Mobile App')
                            ->columnSpanFull(),
                        TextInput::make('title')
                            ->label('Judul Popup')
                            ->required()
                            ->default('Aplikasi EV Charge ID Telah Hadir!'),
                        TextInput::make('subtitle')
                            ->label('Sub-judul')
                            ->required()
                            ->default('Temukan lokasi SPKLU, info status charging realtime, dan panduan rute akurat langsung dari ponsel Anda.'),
                        Textarea::make('description')
                            ->label('Deskripsi Lengkap (Opsional)')
                            ->rows(3)
                            ->columnSpanFull(),
                        Toggle::make('qr_code_enabled')
                            ->label('Tampilkan QR Code di Layar Desktop')
                            ->default(true),
                    ])->columns(2),

                Section::make('Konfigurasi Aplikasi Android')
                    ->description('Pengaturan link download Android (Google Play Store, Direct APK, IAS, dsb.)')
                    ->schema([
                        Toggle::make('android_enabled')
                            ->label('Tampilkan Opsi Android')
                            ->default(true),
                        TextInput::make('android_version')
                            ->label('Label Versi / Status Android')
                            ->default('v1.0.1 (Tersedia)')
                            ->placeholder('Contoh: v1.0.1 atau Tersedia'),
                        TextInput::make('android_url')
                            ->label('Link Download Android (URL)')
                            ->url()
                            ->required()
                            ->default('https://play.google.com/store/apps/details?id=id.sagansa.ev')
                            ->columnSpanFull(),
                        TextInput::make('android_button_text')
                            ->label('Teks Tombol Android')
                            ->default('Download di Google Play')
                            ->required(),
                        TextInput::make('android_notes')
                            ->label('Catatan Android')
                            ->default('Mendukung Android 7.0+ ke atas')
                            ->placeholder('Contoh: Mendukung Android 7.0+'),
                    ])->columns(2),

                Section::make('Konfigurasi Aplikasi iOS')
                    ->description('Pengaturan status iOS (Coming Soon / TestFlight / App Store)')
                    ->schema([
                        Toggle::make('ios_enabled')
                            ->label('Tampilkan Opsi iOS')
                            ->default(true),
                        Select::make('ios_status')
                            ->label('Status iOS')
                            ->options([
                                'coming_soon' => 'Segera Hadir (Coming Soon)',
                                'testflight' => 'TestFlight Beta',
                                'app_store' => 'Tersedia di Apple App Store',
                            ])
                            ->default('coming_soon')
                            ->required(),
                        TextInput::make('ios_version')
                            ->label('Label Versi / Status iOS')
                            ->default('Segera Hadir')
                            ->placeholder('Contoh: Segera Hadir atau v1.0.0'),
                        TextInput::make('ios_url')
                            ->label('Link iOS App / TestFlight (Kosongkan jika Coming Soon)')
                            ->url()
                            ->nullable()
                            ->columnSpanFull(),
                        TextInput::make('ios_button_text')
                            ->label('Teks Tombol iOS')
                            ->default('Download di App Store'),
                        TextInput::make('ios_notes')
                            ->label('Catatan iOS')
                            ->default('Dalam tahap review App Store')
                            ->placeholder('Contoh: Dalam tahap review App Store'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ToggleColumn::make('is_active')
                    ->label('Popup Aktif'),
                ToggleColumn::make('is_closable')
                    ->label('Bisa Ditutup'),
                TextColumn::make('title')
                    ->label('Judul')
                    ->weight('bold')
                    ->limit(35),
                IconColumn::make('android_enabled')
                    ->label('Android')
                    ->boolean(),
                TextColumn::make('android_url')
                    ->label('Link Android')
                    ->limit(30),
                IconColumn::make('ios_enabled')
                    ->label('iOS')
                    ->boolean(),
                TextColumn::make('ios_status')
                    ->label('Status iOS')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'app_store' => 'success',
                        'testflight' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'app_store' => 'Tersedia di App Store',
                        'testflight' => 'TestFlight Beta',
                        default => 'Segera Hadir',
                    }),
                TextColumn::make('updated_at')
                    ->label('Terakhir Diubah')
                    ->dateTime('d M Y H:i')
                    ->timezone('Asia/Jakarta')
                    ->sortable(),
            ])
            ->recordActions([
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppDownloadSettings::route('/'),
            'create' => Pages\CreateAppDownloadSetting::route('/create'),
            'edit' => Pages\EditAppDownloadSetting::route('/{record}/edit'),
        ];
    }
}
