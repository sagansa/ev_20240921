<?php

namespace App\Filament\Resources\Panel\EsdmSpkluStationResource\Pages;

use App\Filament\Resources\Panel\EsdmSpkluStationResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditEsdmSpkluStation extends EditRecord
{
    protected static string $resource = EsdmSpkluStationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->fill($data);

        $latChanged = isset($data['geo_verified_lat']) && $data['geo_verified_lat'] !== null;
        $lngChanged = isset($data['geo_verified_lng']) && $data['geo_verified_lng'] !== null;

        if ($latChanged && $lngChanged) {
            $record->geo_verification = 'manual_fixed';
            $record->geo_verified_source = 'manual';
            $record->latitude = $data['geo_verified_lat'];
            $record->longitude = $data['geo_verified_lng'];
        }

        $record->save();

        return $record;
    }
}
