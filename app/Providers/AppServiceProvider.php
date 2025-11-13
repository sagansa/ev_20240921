<?php

namespace App\Providers;

use App\Models\ChargerLocation;
use App\Models\LocationReport;
use App\Observers\ChargerLocationObserver;
use App\Observers\LocationReportObserver;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\ServiceProvider;

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
