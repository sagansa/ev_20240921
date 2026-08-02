<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Poll status real-time konektor ESDM Singgat tiap 10 menit.
// Mengisi esdm_singgat_connector_status (snapshot) + _status_log (history
// perubahan) + esdm_singgat_station_status (agregat per stasiun).
// Catatan: setiap hit ~35s & 5.6MB; rate-limit ESDM 400/window — aman.
Schedule::command('esdm:poll-status')
    ->everyTenMinutes()
    ->withoutOverlapping(15)         // jangan jalankan paralel; kill setelah 15 menit
    ->runInBackground()              // jangan blokir scheduler utama
    ->name('esdm-poll-status')
    ->description('Poll status real-time konektor ESDM Singgat');

// Hydrate tabel kanonik charging_stations dari master data ESDM.
// Re-roll master (nama, tarif, provider, geo) — cukup harian karena master
// ESDM hanya berubah saat import manual (esdm:import-singgat). Berbeda dari
// poll-status yang menangani status real-time (konektor) saja.
Schedule::command('esdm:hydrate-canonical')
    ->dailyAt('03:00')
    ->withoutOverlapping(60)
    ->name('esdm-hydrate-canonical')
    ->description('Hydrate charging_stations (kanonik) dari master ESDM — daily');

