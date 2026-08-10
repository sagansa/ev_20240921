<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Models\Charge;
use App\Models\ChargingStation;
use Illuminate\Support\Facades\Auth;

/**
 * Gate "pernah menyelesaikan sesi charging di station ini" — dipakai fitur
 * user-generated content (review Fase 1, foto Fase 2) agar hanya kontributor
 * yg benar-benat pernah charging boleh posting.
 *
 * Konsisten dgn spec: ketat by `charges.charging_station_id` +
 * `is_finish_charging = true`. Sesi lama tanpa station_id tidak mengaktifkan gate.
 */
trait RequiresCompletedSession
{
    /**
     * Apakah user saat ini punya minimal satu sesi charging selesai di station?
     */
    protected function hasCompletedSession(ChargingStation $station): bool
    {
        return Charge::query()
            ->where('user_id', Auth::id())
            ->where('charging_station_id', $station->id)
            ->where('is_finish_charging', true)
            ->exists();
    }

    /**
     * Cek station terdaftar PLN (source = 'pln'). Review/foto hanya utk PLN.
     */
    protected function isPlnStation(ChargingStation $station): bool
    {
        return $station->source === 'pln';
    }
}
