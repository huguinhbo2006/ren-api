<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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
        $this->configureSQLite();
        $this->configureUrl();
        $this->configureRateLimiting();
    }

    /**
     * Configura SQLite para máximo rendimiento y consistencia.
     * - WAL mode: permite lecturas concurrentes mientras se escribe.
     * - Foreign keys: habilita integridad referencial (desactivada por defecto en SQLite).
     * - Busy timeout: evita errores de "database is locked" bajo carga.
     */
    private function configureSQLite(): void
    {
        Event::listen(ConnectionEstablished::class, function (ConnectionEstablished $event) {
            if ($event->connection->getDriverName() === 'sqlite') {
                $pdo = $event->connection->getPdo();
                $pdo->exec('PRAGMA journal_mode=WAL');
                $pdo->exec('PRAGMA foreign_keys=ON');
                $pdo->exec('PRAGMA busy_timeout=5000');
                $pdo->exec('PRAGMA synchronous=NORMAL');
                $pdo->exec('PRAGMA cache_size=-64000'); // 64 MB cache
                $pdo->exec('PRAGMA temp_store=MEMORY');
            }
        });
    }

    /**
     * Fuerza HTTPS en producción para la generación de URLs.
     */
    private function configureUrl(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }

    /**
     * Configura los rate limiters del API de Rentame.
     * - login:   5 intentos/min por email+IP (protección brute force)
     * - api:     60 req/min por usuario o IP
     * - uploads: 20 subidas/min por usuario o IP
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(
                config('rentame.rate_limits.login', 5)
            )->by($request->input('email').'|'.$request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(
                config('rentame.rate_limits.api', 60)
            )->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(
                config('rentame.rate_limits.uploads', 20)
            )->by($request->user()?->id ?: $request->ip());
        });
    }
}
