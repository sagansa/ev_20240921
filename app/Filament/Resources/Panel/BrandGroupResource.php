<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Resources\Panel\BrandGroupResource\Pages;
use App\Models\BrandGroup;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BrandGroupResource extends Resource
{
    protected static ?string $model = BrandGroup::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-library';

    protected static string | \UnitEnum | null $navigationGroup = 'Referensi Kendaraan';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'Grup Induk Perusahaan';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Grup Induk Perusahaan';
    }

    public static function getNavigationLabel(): string
    {
        return 'Grup Induk Perusahaan';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Grup Induk Perusahaan')
                ->description('Klaster industri lintas brand (mis. SAIC = MG + Wuling + Maxus). '
                    . 'Angka leaderboard grup di aplikasi otomatis segar setelah penyimpanan.')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->string()
                        ->maxLength(100)
                        ->unique(ignoreRecord: true)
                        ->label('Nama Grup')
                        ->placeholder('cth. SAIC, Toyota Group, BYD Group')
                        ->autofocus(),

                    // HasMany inverse: menyimpan = set brand_group_id pada brand
                    // yang dipilih + melepas (NULL-kan) yang dicopot.
                    Select::make('brandVehicles')
                        ->relationship('brandVehicles', 'name')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->label('Brand Anggota')
                        ->helperText('Brand yang tergabung dalam grup ini — kosongkan semua untuk membubarkan grup.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Grup')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('brand_vehicles_count')
                    ->counts('brandVehicles')
                    ->label('Jumlah Brand')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBrandGroups::route('/'),
            'create' => Pages\CreateBrandGroup::route('/create'),
            'edit' => Pages\EditBrandGroup::route('/{record}/edit'),
        ];
    }
}
