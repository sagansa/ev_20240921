<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EvController;
use App\Http\Controllers\PlnChargerLocationController;
use Filament\Http\Controllers\Auth\LoginController;
use Filament\Http\Controllers\Auth\RegisterController;

// Route::get('/', function () {
//     return view('ev
//     ');
// });

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

Route::get('/', [EvController::class, 'plnMap'])->name('home');

// Route::get('/', [PlnChargerLocationController::class, 'index'])->name('home');
Route::get('/pln-map', [EvController::class, 'plnMap'])->name('pln-map');
Route::get('/map', [EvController::class, 'map'])->name('map');
Route::get('/providers', [EvController::class, 'providers'])->name('providers');
Route::get('/products', [EvController::class, 'products'])->name('products');
Route::get('/contact', [EvController::class, 'contact'])->name('contact');
Route::get('/chargers', [EvController::class, 'chargers'])->name('chargers');
Route::get('/get-cities/{province}', [EvController::class, 'getCities'])->name('get.cities');
Route::get('/get-type-chargers/{current}', [EvController::class, 'getTypeChargers'])->name('get.type.chargers');
Route::get('/get-power-chargers/{type}', [EvController::class, 'getPowerChargers'])->name('get.power.chargers');
Route::get('/get-provider-details/{provider}', [EvController::class, 'getProviderDetails']);
Route::get('/filter-pln-locations/{chargingType?}/{locationCategory?}', [EvController::class, 'filterPlnLocations'])->name('filter.pln.locations');
