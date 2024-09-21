<?php

namespace App\Filament\Resources\Panel\ChargeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Panel\ChargeResource;
use App\Models\Charge;

class CreateCharge extends CreateRecord
{
    protected static string $resource = ChargeResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'km_before' => 0,
            'finish_charging_before' => 0,
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        $vehicleId = $data['vehicle_id'] ?? null;

        if ($vehicleId) {
            $data['km_before'] = ChargeResource::getLatestKmNowForVehicle($vehicleId);
            $data['finish_charging_before'] = ChargeResource::getLatestChargingNowForVehicle($vehicleId);
        }

        return $data;
    }

    private function getLatestKmNowForVehicle($vehicleId)
    {
        $latestKm = Charge::where('vehicle_id', $vehicleId)
            ->latest('date')
            ->first();

        return $latestKm ? $latestKm->km_now : 0;
    }

    private function getLatestChargingNowForVehicle($vehicleId)
    {
        $latestCharge = Charge::where('vehicle_id', $vehicleId)
            ->latest('date')
            ->first();

        return $latestCharge ? $latestCharge->finish_charging_now : 0;
    }

}
