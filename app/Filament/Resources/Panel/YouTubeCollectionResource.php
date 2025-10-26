<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Resources\Panel\YouTubeCollectionResource\Pages;
use App\Models\YouTubeCollection;
use Filament\{Tables, Resources};
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class YouTubeCollectionResource extends Resource
{
    protected static ?string $model = YouTubeCollection::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $navigationGroup = 'Content Management';

    protected static ?string $pluralModelLabel = 'YouTube Collections';

    protected static ?string $modelLabel = 'YouTube Collection';

    public static function canAccess(): bool
    {
        // Only allow super-admin access
        $user = Auth::user();
        return $user && $user->hasRole('super_admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('YouTube Collection Information')
                    ->description('Enter the details for the YouTube video')
                    ->schema([
                        TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('video_id')
                            ->label('Video ID')
                            ->helperText('The YouTube video ID (e.g., dQw4w9WgXcQ from https://youtube.com/watch?v=dQw4w9WgXcQ)')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(4)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        TextInput::make('thumbnail_url')
                            ->label('Thumbnail URL')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('channel_name')
                            ->label('Channel Name')
                            ->maxLength(255),
                        TextInput::make('category')
                            ->label('Category')
                            ->maxLength(255),
                        TextInput::make('view_count')
                            ->label('View Count')
                            ->numeric()
                            ->default(0),
                        DatePicker::make('published_at')
                            ->label('Published At'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('channel_name')
                    ->label('Channel')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('view_count')
                    ->label('Views')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                TextColumn::make('published_at')
                    ->label('Published')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Action::make('view')
                    ->url(fn ($record) => route('youtube.show', $record->id))
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-eye'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListYouTubeCollections::route('/'),
            'create' => Pages\CreateYouTubeCollection::route('/create'),
            'edit' => Pages\EditYouTubeCollection::route('/{record}/edit'),
        ];
    }
}