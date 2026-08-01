<?php

namespace App\Providers;

use App\Models\ChargerLocation;
use App\Models\LocationReport;
use App\Models\PersonalAccessToken;
use App\Observers\ChargerLocationObserver;
use App\Observers\LocationReportObserver;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Use a custom Sanctum token model pinned to the `sagansa_user`
        // connection (same DB as the User model). Without this, tokens are
        // written on the owner's connection (sagansa_user) but read on the
        // default connection (ev), so every bearer-auth request 401s.
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        Select::configureUsing(function (Select $select) {
            $select->preload()->native(false);
        });

        DatePicker::configureUsing(function(DatePicker $datePicker) {
            $datePicker->native(false)->inlineLabel();
        });

        Toggle::configureUsing(function(Toggle $toggle) {
            $toggle->inlineLabel();
        });

        // Section::configureUsing(function(Section $section) {
        //     $section->columns()->compact();
        // });
        
        // Register model observers
        ChargerLocation::observe(ChargerLocationObserver::class);
        LocationReport::observe(LocationReportObserver::class);
    }
}
