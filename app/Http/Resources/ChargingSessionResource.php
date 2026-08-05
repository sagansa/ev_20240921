<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Serializer untuk sesi charging (Charge) sebagai pencatat sesi mobile.
 *
 * Mengikuti kontrak eksplisit-array (preseden SpkluLocationResource): tipe
 * field di-cast eksplisit, relasi hanya serialize saat eager-loaded. Field
 * snapshot station (`station_*`) selalu di-serve agar riwayat user utuh walau
 * charging_stations canonical berubah/dihapus.
 */
class ChargingSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date ? Carbon::parse($this->date)->toDateString() : null,

            // Lokasi — dua kemungkinan: charging_stations (mobile SPKLU) atau
            // charger_locations (legacy Filament admin). Snapshot dipakai utk
            // sesi mobile; utk sesi legacy (snapshot NULL) fallback ke relasi
            // chargerLocation supaya nama/alamat/provider tetap terisi.
            'charging_station_id' => $this->charging_station_id,
            'charger_location_id' => $this->charger_location_id,
            'station_name' => $this->station_name_snapshot ?? $this->chargerLocation?->name,
            'station_address' => $this->station_address_snapshot ?? $this->chargerLocation?->address,
            'station_latitude' => isset($this->station_lat_snapshot)
                ? (float) $this->station_lat_snapshot
                : ($this->chargerLocation?->latitude !== null ? (float) $this->chargerLocation->latitude : null),
            'station_longitude' => isset($this->station_lng_snapshot)
                ? (float) $this->station_lng_snapshot
                : ($this->chargerLocation?->longitude !== null ? (float) $this->chargerLocation->longitude : null),
            'station_provider' => $this->station_provider_snapshot ?? $this->whenLoaded('chargerLocation', fn () => $this->chargerLocation?->provider?->name),

            // Kendaraan (opsional — sesi mobile tanpa vehicle).
            'vehicle_id' => $this->vehicle_id,
            'vehicle' => $this->whenLoaded('vehicle', function () {
                return [
                    'id' => $this->vehicle?->id,
                    'license_plate' => $this->vehicle?->license_plate,
                ];
            }),

            // Pengukuran sesi (semua opsional — input cepat lapangan).
            'km_before' => isset($this->km_before) ? (float) $this->km_before : null,
            'km_now' => isset($this->km_now) ? (float) $this->km_now : null,
            'start_charging_now' => isset($this->start_charging_now) ? (float) $this->start_charging_now : null,
            'finish_charging_now' => isset($this->finish_charging_now) ? (float) $this->finish_charging_now : null,
            'finish_charging_before' => isset($this->finish_charging_before) ? (float) $this->finish_charging_before : null,
            'is_finish_charging' => (bool) $this->is_finish_charging,

            // Energi & biaya.
            'kwh' => isset($this->kWh) ? (float) $this->kWh : null,
            'is_kwh_measured' => (bool) $this->is_kwh_measured,
            'parking_cost' => isset($this->parking) ? (float) $this->parking : null,
            'street_lighting_tax' => isset($this->street_lighting_tax) ? (float) $this->street_lighting_tax : null,
            'value_added_tax' => isset($this->value_added_tax) ? (float) $this->value_added_tax : null,
            'admin_cost' => isset($this->admin_cost) ? (float) $this->admin_cost : null,
            'total_cost' => isset($this->total_cost) ? (float) $this->total_cost : null,

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
