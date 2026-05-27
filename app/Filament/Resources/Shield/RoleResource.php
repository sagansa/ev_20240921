<?php

namespace App\Filament\Resources\Shield;

use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use App\Filament\Resources\Shield\RoleResource\Pages;
use BezhanSalleh\FilamentShield\Support\Utils;
use BezhanSalleh\FilamentShield\Traits\HasShieldFormComponents;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Panel;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class RoleResource extends Resource implements HasShieldPermissions
{
    use HasShieldFormComponents;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'default' => 1,
                    'lg' => 3,
                ])
                ->schema([
                    Section::make()
                        ->schema([
                            TextInput::make('name')
                                ->label(__('filament-shield::filament-shield.field.name'))
                                ->unique(ignoreRecord: true)
                                ->required()
                                ->maxLength(255),

                            TextInput::make('guard_name')
                                ->label(__('filament-shield::filament-shield.field.guard_name'))
                                ->default(Utils::getFilamentAuthGuard())
                                ->nullable()
                                ->maxLength(255),

                            static::getSelectAllFormComponent(),
                        ])
                        ->columnSpan([
                            'lg' => 1,
                        ]),

                    Tabs::make('Permissions')
                        ->contained()
                        ->tabs([
                            static::getTabFormComponentForResources(),
                            static::getTabFormComponentForPage(),
                            static::getTabFormComponentForWidget(),
                            static::getTabFormComponentForCustomPermissions(),
                        ])
                        ->columnSpan([
                            'lg' => 2,
                        ]),
                ])
                ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->weight(FontWeight::Medium)
                    ->label(__('filament-shield::filament-shield.column.name'))
                    ->formatStateUsing(fn ($state): string => Str::headline($state))
                    ->searchable(),
                TextColumn::make('guard_name')
                    ->badge()
                    ->color('warning')
                    ->label(__('filament-shield::filament-shield.column.guard_name')),
                TextColumn::make('permissions_count')
                    ->badge()
                    ->label(__('filament-shield::filament-shield.column.permissions'))
                    ->counts('permissions')
                    ->color('primary'),
                TextColumn::make('updated_at')
                    ->label(__('filament-shield::filament-shield.column.updated_at'))
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'view' => Pages\ViewRole::route('/{record}'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function getCluster(): ?string
    {
        return Utils::getResourceCluster() ?? static::$cluster;
    }

    public static function getModel(): string
    {
        return Utils::getRoleModel();
    }

    public static function getModelLabel(): string
    {
        return __('filament-shield::filament-shield.resource.label.role');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-shield::filament-shield.resource.label.roles');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) config('filament-shield.shield_resource.should_register_navigation', true);
    }


    public static function getNavigationLabel(): string
    {
        return __('filament-shield::filament-shield.nav.role.label');
    }


    public static function getNavigationSort(): ?int
    {
        return config('filament-shield.shield_resource.navigation_sort');
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return Utils::getResourceSlug();
    }

    public static function getNavigationBadge(): ?string
    {
        return config('filament-shield.shield_resource.navigation_badge', true)
            ? strval(static::getEloquentQuery()->count())
            : null;
    }

    public static function isScopedToTenant(): bool
    {
        return (bool) config('filament-shield.shield_resource.is_scoped_to_tenant', true);
    }

    public static function canGloballySearch(): bool
    {
        return (bool) config('filament-shield.shield_resource.is_globally_searchable', false)
            && count(static::getGloballySearchableAttributes())
            && static::canViewAny();
    }

    protected static function getLegacyResourcePermissionKey(string $resourceFqcn, string $action): string
    {
        $cleanName = str_replace(['App\Filament\Resources\\', 'Resource'], '', $resourceFqcn);
        $segments = explode('\\', $cleanName);
        $formattedSegments = array_map(function($segment) {
            return Str::snake($segment, '::');
        }, $segments);
        $resourceKey = implode('::', $formattedSegments);
        $prefix = Str::snake($action);
        return "{$prefix}_{$resourceKey}";
    }

    public static function getResourcePermissionOptions(array $entity): array
    {
        $options = [];
        foreach ($entity['permissions'] as $action => $permission) {
            $legacyKey = static::getLegacyResourcePermissionKey($entity['resourceFqcn'], $action);
            $options[$legacyKey] = $permission['label'];
        }
        return $options;
    }

    public static function getPageOptions(): array
    {
        $options = [];
        foreach (FilamentShield::getPages() as $page) {
            $className = class_basename($page['pageFqcn']);
            $legacyKey = "page_{$className}";
            $label = reset($page['permissions']) ?: $className;
            $options[$legacyKey] = $label;
        }
        return $options;
    }

    public static function getWidgetOptions(): array
    {
        $options = [];
        foreach (FilamentShield::getWidgets() as $widget) {
            $className = class_basename($widget['widgetFqcn']);
            $legacyKey = "widget_{$className}";
            $label = reset($widget['permissions']) ?: $className;
            $options[$legacyKey] = $label;
        }
        return $options;
    }
}
