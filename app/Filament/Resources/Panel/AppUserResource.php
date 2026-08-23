<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Resources\Panel\AppUserResource\Pages;
use App\Models\AppUser;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Read-only view of app_users — pantau asal login & platform user EV.
 */
class AppUserResource extends Resource
{
    protected static ?string $model = AppUser::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static string|\UnitEnum|null $navigationGroup = 'Admin';

    protected static ?string $navigationLabel = 'App Users';

    protected static ?string $modelLabel = 'App User';

    protected static ?string $pluralModelLabel = 'App Users';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->columns([
                TextColumn::make('user_id')
                    ->label('User ID')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('user.name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(30),
                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                TextColumn::make('provider')
                    ->label('Provider')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'google' => 'success',
                        'apple' => 'info',
                        default => 'gray',
                    })
                    ->placeholder('—'),
                TextColumn::make('platform')
                    ->label('Platform')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'android' => 'success',
                        'ios' => 'info',
                        'web' => 'warning',
                        default => 'gray',
                    })
                    ->placeholder('—'),
                TextColumn::make('login_count')
                    ->label('Login Count')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('first_login_at')
                    ->label('First Login')
                    ->dateTime('d M Y H:i')
                    ->timezone('Asia/Jakarta')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime('d M Y H:i')
                    ->timezone('Asia/Jakarta')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('source')
                    ->label('Source')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'login' ? 'success' : 'warning'),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('platform')
                    ->label('Platform')
                    ->options([
                        'android' => 'Android',
                        'ios' => 'iOS',
                        'web' => 'Web',
                    ]),
                \Filament\Tables\Filters\SelectFilter::make('provider')
                    ->label('Provider')
                    ->options([
                        'google' => 'Google',
                        'apple' => 'Apple',
                    ]),
            ])
            ->defaultSort('last_login_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppUsers::route('/'),
            'view' => Pages\ViewAppUser::route('/{record}'),
        ];
    }
}
