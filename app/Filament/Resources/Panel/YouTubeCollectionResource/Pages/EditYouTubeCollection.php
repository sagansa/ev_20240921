<?php

namespace App\Filament\Resources\Panel\YouTubeCollectionResource\Pages;

use App\Filament\Resources\Panel\YouTubeCollectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditYouTubeCollection extends EditRecord
{
    protected static string $resource = YouTubeCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}