<?php

namespace App\Models;

use App\Models\Concerns\UsesDefaultConnectionWhenTesting;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Charge extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use UsesDefaultConnectionWhenTesting;

    protected $connection = 'ev'; // Use the ev database connection

    protected $table = 'charges';

    protected $fillable = [
        'image_start',
        'image_finish',
        'vehicle_id',
        'battery_id',
        'date',
        'charger_location_id',
        'charger_id',
        'charging_station_id',
        'station_name_snapshot',
        'station_address_snapshot',
        'station_lat_snapshot',
        'station_lng_snapshot',
        'station_provider_snapshot',
        'station_chargerbox_id_snapshot',
        'station_chargerbox_name_snapshot',
        'station_chargerbox_type_snapshot',
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

    /**
     * Sesi baru default "sedang berlangsung" (is_finish_charging = false) bila
     * client tidak mengirim field. Mobile saat ini selalu mengirim boolean eksplisit,
     * tapi default DB yg benar penting utk API consumer lain / partial update.
     */
    protected $attributes = [
        'is_finish_charging' => false,
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function battery()
    {
        return $this->belongsTo(Battery::class);
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
     *  0. Charger box spesifik terpilih user (station_chargerbox_type_snapshot)
     *     — paling akurat per-sesi (mobile picker).
     *  1. charger.typeCharger.name (nama konektor: Type 2/AC GBT=AC, CCS2/Chademo/DC*=DC).
     *  2. chargingStation.type_charge canonical (medium=AC, fast/ultra=DC).
     *  3. Heuristic nama snapshot — fallback terakhir.
     * Return null bila tidak dapat ditentukan.
     */
    public function getChargingTypeAttribute(): ?string
    {
        // (0) Charger box spesifik terpilih user (mobile SPKLU picker) —
        //     paling akurat karena mengalahkan tipe station campuran.
        $fromChargerbox = self::resolveCanonicalTypeCharge($this->station_chargerbox_type_snapshot);
        if ($fromChargerbox !== null) {
            return $fromChargerbox;
        }

        // (1) Charger spesifik → TypeCharger (nama konektor).
        if ($this->charger && $this->charger->typeCharger) {
            $resolved = self::resolveConnectorToAcDc($this->charger->typeCharger->name);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        // (2) Canonical station.
        $fromStation = self::resolveCanonicalTypeCharge($this->chargingStation?->type_charge);
        if ($fromStation !== null) {
            return $fromStation;
        }
        foreach ($this->chargingStation?->chargers ?? [] as $charger) {
            $fromCharger = self::resolveCanonicalTypeCharge($charger->type_charge ?? null);
            if ($fromCharger !== null) {
                return $fromCharger;
            }
        }

        // (3) Fallback heuristic snapshot nama.
        $haystack = strtoupper(trim(($this->station_name_snapshot ?? '').' '.($this->station_provider_snapshot ?? '')));
        if ($haystack === '') {
            return null;
        }
        $dcTokens = ['DC', 'FAST', 'ULTRA', 'CCS', 'CHADEMO', 'SUPERCHARGER',
            '50KW', '60KW', '100KW', '120KW', '150KW', '200KW'];
        foreach ($dcTokens as $token) {
            if (str_contains($haystack, $token)) {
                return 'DC';
            }
        }

        return 'AC';
    }

    /**
     * Petakan nama konektor TypeCharger → AC/DC.
     * Data produksi memakai nama konektor (bukan enum AC/DC):
     *  AC: "Type 2", "AC GBT", dst.
     *  DC: "CCS2", "Chademo", "DC GBT", dst.
     * Heuristic: mengandung "DC" / nama DC populer → DC; sisanya → AC.
     */
    public static function resolveConnectorToAcDc(?string $connectorName): ?string
    {
        $name = strtoupper(trim((string) $connectorName));
        if ($name === '') {
            return null;
        }
        // DC: eksplisit ber-prefix "DC" atau nama konektor DC dikenal.
        if (str_starts_with($name, 'DC') || in_array($name, ['CCS2', 'CCS', 'CHADEMO', 'SUPERCHARGER'], true)) {
            return 'DC';
        }
        // AC: eksplisit ber-prefix "AC" atau konektor AC dikenal (Type 2).
        if (str_starts_with($name, 'AC') || in_array($name, ['TYPE 2', 'TYPE2', 'J1772', 'GB/T AC'], true)) {
            return 'AC';
        }

        // Tidak dikenali (mis. nama baru) → null (caller fallback ke sumber lain).
        return null;
    }

    /** Petakan type_charge canonical station → AC/DC. */
    public static function resolveCanonicalTypeCharge(?string $typeCharge): ?string
    {
        $key = strtolower(trim((string) $typeCharge));

        return match (true) {
            in_array($key, ['medium', 'standard', 'mediumcharging', 'slowcharging', 'slow', 'ac'], true) => 'AC',
            in_array($key, ['fast', 'ultra_fast', 'ultrafast', 'fastcharging', 'ultrafastcharging'], true) => 'DC',
            default => null,
        };
    }

    public function currentCharger()
    {
        return $this->charger->currentCharger();
    }
}
