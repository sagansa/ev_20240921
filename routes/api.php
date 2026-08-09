<?php

use App\Http\Controllers\Api\V1\AdvertisementController;
use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BatteryController;
use App\Http\Controllers\Api\V1\ChargerLocationController;
use App\Http\Controllers\Api\V1\ChargingSessionController;
use App\Http\Controllers\Api\V1\ContributorController;
use App\Http\Controllers\Api\V1\DualSourceLocationController;
use App\Http\Controllers\Api\V1\FuelPriceController;
use App\Http\Controllers\Api\V1\HomeChargingDiscountController;
use App\Http\Controllers\Api\V1\LocationCategoryController;
use App\Http\Controllers\Api\V1\LocationReportController;
use App\Http\Controllers\Api\V1\PlnChargerLocationController;
use App\Http\Controllers\Api\V1\ProviderController;
use App\Http\Controllers\Api\V1\ScrapeIngestController;
use App\Http\Controllers\Api\V1\SocialAuthController;
use App\Http\Controllers\Api\V1\SpkluLocationController;
use App\Http\Controllers\Api\V1\StateOfHealthController;
use App\Http\Controllers\Api\V1\VehicleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/auth/verify-otp', [AuthController::class, 'confirmVerification']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

    // Public EV data routes
    Route::get('/charging-locations/nearby', [ChargerLocationController::class, 'nearby']);
    Route::get('/charging-locations', [ChargerLocationController::class, 'index']);
    Route::get('/charging-locations/{chargerLocation}', [ChargerLocationController::class, 'show']);
    Route::get('/pln-charging-locations', [PlnChargerLocationController::class, 'index']);
    Route::get('/location-categories', [LocationCategoryController::class, 'index']);

    // SPKLU data routes (from data_spklu.json)
    Route::get('/spklu', [SpkluLocationController::class, 'index']);
    Route::get('/spklu/{id}', [SpkluLocationController::class, 'show']);
    Route::get('/meta/filters', [SpkluLocationController::class, 'metaFilters']);

    // Providers
    // Social auth (no middleware — public endpoints)
    Route::post('/auth/google', [SocialAuthController::class, 'googleLogin']);
    Route::post('/auth/apple', [SocialAuthController::class, 'appleLogin']);

    Route::get('/providers', [ProviderController::class, 'index']);

    // Public advertisement routes (for displaying ads to users and tracking metrics)
    Route::get('/ads/mobile', [AdvertisementController::class, 'mobile']);
    Route::get('/ads/web', [AdvertisementController::class, 'web']);
    Route::post('/advertisements/{advertisement}/impression', [AdvertisementController::class, 'recordImpression']);
    Route::post('/advertisements/{advertisement}/click', [AdvertisementController::class, 'recordClick']);

    // Protected routes
    Route::middleware(['auth:sanctum'])->group(function () {
        // Authentication
        Route::post('/auth/refresh-token', [AuthController::class, 'refreshToken']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Social auth status & logout
        Route::get('/auth/status', [SocialAuthController::class, 'status']);
        Route::post('/auth/social-logout', [SocialAuthController::class, 'logout']);

        // User-specific routes
        Route::get('/vehicles/options', [VehicleController::class, 'options']);
        Route::apiResource('vehicles', VehicleController::class);
        Route::apiResource('charging-locations', ChargerLocationController::class)->except(['index', 'show']);
        Route::get('/charging-sessions/analytics', [ChargingSessionController::class, 'analytics']);
        Route::get('/charging-sessions/latest', [ChargingSessionController::class, 'latest']);
        Route::get('/charging-sessions/journey', [ChargingSessionController::class, 'journey']);
        Route::apiResource('charging-sessions', ChargingSessionController::class);
        Route::get('/fuel-prices', [FuelPriceController::class, 'index']);
        Route::apiResource('state-of-health', StateOfHealthController::class);
        Route::get('/state-of-health/{vehicleId}/trend-analysis', [StateOfHealthController::class, 'trendAnalysis']);
        Route::apiResource('batteries', BatteryController::class);
        Route::post('/vehicles/{vehicle}/swap-battery', [BatteryController::class, 'swap']);
        Route::apiResource('home-charging-discounts', HomeChargingDiscountController::class);
        Route::post('/home-charging-discounts/apply', [HomeChargingDiscountController::class, 'apply']);

        // Analytics
        Route::get('/analytics/charging-patterns', [AnalyticsController::class, 'chargingPatterns']);
        Route::get('/analytics/cost-analysis', [AnalyticsController::class, 'costAnalysis']);
        Route::get('/analytics/reports', [AnalyticsController::class, 'reports']);
        Route::get('/analytics/visitor-profiles', [AnalyticsController::class, 'visitorProfiles']);

        // Dual-source location management (admin routes)
        Route::prefix('admin')->group(function () {
            Route::post('/pln-locations/import', [DualSourceLocationController::class, 'importPlnLocations']);
            Route::put('/pln-locations/{plnLocation}', [DualSourceLocationController::class, 'updatePlnLocation']);
            Route::get('/community-locations/pending', [DualSourceLocationController::class, 'getPendingCommunityLocations']);
            Route::post('/community-locations/{chargerLocation}/verify', [DualSourceLocationController::class, 'verifyCommunityLocation']);
            Route::post('/community-locations/{chargerLocation}/reject', [DualSourceLocationController::class, 'rejectCommunityLocation']);
            Route::get('/locations/duplicates', [DualSourceLocationController::class, 'detectDuplicates']);
            Route::post('/locations/consolidate', [DualSourceLocationController::class, 'consolidateLocations']);

            // Location reports
            Route::get('/reports/pending', [LocationReportController::class, 'getPendingReports']);

            // Scrape ingestion (Chrome extension)
            Route::post('/scrape/ingest', [ScrapeIngestController::class, 'ingest']);
        });

        // Community location submission
        Route::post('/community-locations', [DualSourceLocationController::class, 'submitCommunityLocation']);

        // Location reporting
        Route::post('/locations/{chargerLocation}/report', [LocationReportController::class, 'reportLocation']);
        Route::get('/locations/{chargerLocation}/reports', [LocationReportController::class, 'getLocationReports']);
        Route::post('/reports/{report}/process', [LocationReportController::class, 'processReport']);

        // Contributor management
        Route::get('/contributors/profile', [ContributorController::class, 'profile']);
        Route::get('/contributors/leaderboard', [ContributorController::class, 'leaderboard']);
        Route::get('/contributors/{id}/history', [ContributorController::class, 'history']);

        // Advertisement management (admin routes)
        Route::apiResource('advertisements', AdvertisementController::class);
    });
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
