<?php

use App\Services\CanonicalStationHydrateService;

/**
 * Konfigurasi serving SPKLU (lapisan kanonik charging_stations).
 *
 * `serving_source` memilih source mana yang di-serving oleh GET /api/v1/spklu
 * (ESDM vs PLN). Data dari source lain tetap dipertahankan di tabel, hanya
 * tidak disajikan. Switch source cukup lewat env — tanpa perubahan kode.
 */
return [
    'serving_source' => env('SPKLU_SERVING_SOURCE', CanonicalStationHydrateService::SOURCE_PLN),
];
