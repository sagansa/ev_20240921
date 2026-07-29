<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Forms\ImageFileUpload;
use App\Filament\Resources\Panel\AdvertisementResource\Pages;
use App\Models\Advertisement;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class AdvertisementResource extends Resource
{
    protected static ?string $model = Advertisement::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string | \UnitEnum | null $navigationGroup = 'Admin';

    protected static ?string $navigationLabel = 'Advertisements';

    protected static ?string $modelLabel = 'Advertisement';

    protected static ?string $pluralModelLabel = 'Advertisements';

    protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole('super_admin') || Auth::user()?->hasRole('admin') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Iklan')
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul Iklan')
                            ->required(),
                        Select::make('platform')
                            ->label('Platform')
                            ->options([
                                'both' => 'Semua (Mobile & Web)',
                                'mobile' => 'Mobile App',
                                'web' => 'Web App',
                            ])
                            ->default('both')
                            ->required(),
                        TextInput::make('position')
                            ->label('Posisi')
                            ->placeholder('Contoh: banner, top, popup')
                            ->default('banner'),
                        TextInput::make('target_url')
                            ->label('Target URL (Link Klik)')
                            ->url(),
                        ImageFileUpload::make('image_url')
                            ->label('Gambar / Banner Iklan')
                            ->directory('images/advertisements')
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Deskripsi Iklan')
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Pengaturan & Jadwal')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true),
                        DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->default(now()),
                        DatePicker::make('end_date')
                            ->label('Tanggal Selesai (Opsional)')
                            ->nullable(),
                        TextInput::make('impression_count')
                            ->label('Jumlah Impresi')
                            ->numeric()
                            ->disabled(),
                        TextInput::make('click_count')
                            ->label('Jumlah Klik')
                            ->numeric()
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_url')
                    ->label('Banner'),
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('platform')
                    ->label('Platform')
                    ->badge()
                    ->sortable(),
                TextColumn::make('position')
                    ->label('Posisi')
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Aktif'),
                TextColumn::make('impression_count')
                    ->label('Impresi')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('click_count')
                    ->label('Klik')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('Selesai')
                    ->date()
                    ->sortable(),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
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
            'index' => Pages\ListAdvertisements::route('/'),
            'create' => Pages\CreateAdvertisement::route('/create'),
            'edit' => Pages\EditAdvertisement::route('/{record}/edit'),
        ];
    }
}
