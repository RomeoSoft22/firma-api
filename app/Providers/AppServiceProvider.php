<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        // Intentos de la clave de administración de escritorio: contador propio por IP
        // (no comparte cuenta con el límite general de la API).
        RateLimiter::for('clave-escritorio', fn (Request $request) => Limit::perMinute(5)->by('clave-escritorio:'.$request->ip()));
    }
}
