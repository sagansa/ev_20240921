<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Charge extends Model
{
    use UsesDefaultConnectionWhenTesting;

    use HasUuids;
    use HasFactory;
    use SoftDeletes;

    protected $connection = 'ev'; // Use the ev database connection
    protected $table = 'charges';

    protected $fillable = [
        'image_start',
        'image_finish',
        'vehicle_id',
        'date',
        'charger_location_id',
        'charger_id',
        'charging_station_id',
        'station_name_snapshot',
        'station_address_snapshot',
        'station_lat_snapshot',
        'station_lng_snapshot',
        'station_provider_snapshot',
        'km_now',
        'is_finish_charging',
        'start_charging_now',
        'finish_charging_now',
        'parking',
        'kWh',
        'street_lighting_tax',
        'value_added_tax',
        'admin_cost',
        'total_cost',
        'user_id',
        'km_before',
        'finish_charging_before',
        'is_kwh_measured',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function chargerLocation()
    {
        return $this->belongsTo(ChargerLocation::class);
    }

    /** Soft-link ke charging_stations.id (alternatif charger_location_id untuk mobile SPKLU). */
    public function chargingStation()
    {
        return $this->belongsTo(ChargingStation::class, 'charging_station_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function charger()
    {
        return $this->belongsTo(Charger::class, 'charger_id');
    }

    /**
     * Tipe arus charging (AC/DC) — sumber kebenaran deterministik utk badge UI.
     * Cascade sama dgn ChargingSessionController::applyChargingTypeScope:
     *  1. charger.typeCharger.name (enum) — definitif saat input via admin.
     *  2. chargingStation.type_charge canonical (medium=AC, fast/ultra=DC).
     *  3. Heuristic nama snapshot — fallback terakhir.
     * Return null bila tidak dapat ditentukan.
     */
    public function getChargingTypeAttribute(): ?string
    {
        // (1) Charger spesifik → TypeCharger enum.
        if ($this->charger && $this->charger->typeCharger) {
            $name = strtoupper($this->charger->typeCharger->name ?? '');
            if ($name === 'AC' || $name === 'DC') {
                return $name;
            }
        }

        $dcTokens = ['DC', 'FAST', 'ULTRA', 'CCS', 'CHADEMO', 'SUPERCHARGER',
                     '50KW', '60KW', '100KW', '120KW', '150KW', '200KW'];
        $resolveFromTypeCharge = function (?string $t): ?string {
            $key = strtolower(trim((string) $t));
            return match (true) {
                in_array($key, ['medium', 'standard', 'mediumcharging', 'slowcharging', 'slow', 'ac'], true) => 'AC',
                in_array($key, ['fast', 'ultra_fast', 'ultrafast', 'fastcharging', 'ultrafastcharging'], true) => 'DC',
                default => null,
            };
        };

        // (2) Canonical station.
        $fromStation = $resolveFromTypeCharge($this->chargingStation?->type_charge);
        if ($fromStation !== null) {
            return $fromStation;
        }
        foreach ($this->chargingStation?->chargers ?? [] as $charger) {
            $fromCharger = $resolveFromTypeCharge($charger->type_charge ?? null);
            if ($fromCharger !== null) {
                return $fromCharger;
            }
        }

        // (3) Fallback heuristic snapshot nama.
        $haystack = strtoupper(trim(($this->station_name_snapshot ?? '').' '.($this->station_provider_snapshot ?? '')));
        if ($haystack === '') {
            return null;
        }
        foreach ($dcTokens as $token) {
            if (str_contains($haystack, $token)) {
                return 'DC';
            }
        }
        return 'AC';
    }

    public function currentCharger()
    {
        return $this->charger->currentCharger();
    }
}
