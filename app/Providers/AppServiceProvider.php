<?php

namespace App\Providers;

use App\Contracts\PlantServiceInterface;
use App\Contracts\WateringCalculatorServiceInterface;
use App\Contracts\WeatherServiceInterface;
use App\Services\PlantService;
use App\Services\WateringCalculatorService;
use App\Services\WeatherService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PlantServiceInterface::class, PlantService::class);
        $this->app->bind(WeatherServiceInterface::class, WeatherService::class);
        $this->app->bind(WateringCalculatorServiceInterface::class, WateringCalculatorService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
    }
}
