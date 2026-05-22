<?php

namespace App\Filament\Resources\Shield;

use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use App\Filament\Resources\Shield\RoleResource\Pages;
use BezhanSalleh\FilamentShield\Support\Utils;
use BezhanSalleh\FilamentShield\Traits\HasShieldFormComponents;
use Filament\Forms;
use Filament\Forms\Components\Component;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make([
                    'default' => 1,
                    'lg' => 3,
                ])
                ->schema([
                    Forms\Components\Section::make()
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label(__('filament-shield::filament-shield.field.name'))
                                ->unique(ignoreRecord: true)
                                ->required()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('guard_name')
                                ->label(__('filament-shield::filament-shield.field.guard_name'))
                                ->default(Utils::getFilamentAuthGuard())
                                ->nullable()
                                ->maxLength(255),

                            static::getSelectAllFormComponent(),
                        ])
                        ->columnSpan([
                            'lg' => 1,
                        ]),

                    Forms\Components\Tabs::make('Permissions')
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
                Tables\Columns\TextColumn::make('name')
                    ->badge()
                    ->label(__('filament-shield::filament-shield.column.name'))
                    ->formatStateUsing(fn ($state): string => Str::headline($state))
                    ->colors(['primary'])
                    ->searchable(),
                Tables\Columns\TextColumn::make('guard_name')
                    ->badge()
                    ->label(__('filament-shield::filament-shield.column.guard_name')),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->badge()
                    ->label(__('filament-shield::filament-shield.column.permissions'))
                    ->counts('permissions')
                    ->colors(['success']),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('filament-shield::filament-shield.column.updated_at'))
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
        return Utils::isResourceNavigationRegistered();
    }

    public static function getNavigationGroup(): ?string
    {
        return Utils::isResourceNavigationGroupEnabled()
            ? __('filament-shield::filament-shield.nav.group')
            : '';
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-shield::filament-shield.nav.role.label');
    }

    public static function getNavigationIcon(): string
    {
        return __('filament-shield::filament-shield.nav.role.icon');
    }

    public static function getNavigationSort(): ?int
    {
        return Utils::getResourceNavigationSort();
    }

    public static function getSlug(): string
    {
        return Utils::getResourceSlug();
    }

    public static function getNavigationBadge(): ?string
    {
        return Utils::isResourceNavigationBadgeEnabled()
            ? strval(static::getEloquentQuery()->count())
            : null;
    }

    public static function isScopedToTenant(): bool
    {
        return Utils::isScopedToTenant();
    }

    public static function canGloballySearch(): bool
    {
        return Utils::isResourceGloballySearchable() && count(static::getGloballySearchableAttributes()) && static::canViewAny();
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
