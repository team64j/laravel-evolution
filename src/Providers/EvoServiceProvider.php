<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Team64j\LaravelEvolution\Http\Controllers\EvoController;
use Team64j\LaravelEvolution\Legacy;

class EvoServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('evo', fn() => new \DocumentParser());
        $this->app->alias('evo', \DocumentParser::class);
        $this->registerLegacy();
    }

    /**
     * @return void
     */
    public function boot(): void
    {
        $this->registerConfig();
        $this->defineConstants();
        $this->defineRoutes();
    }

    /**
     * @return void
     */
    protected function registerLegacy(): void
    {
        $this->app->singleton('evo.url', fn() => new Legacy\UrlProcessor());
        $this->app->singleton('evo.tpl', fn() => new Legacy\Parser());
        $this->app->singleton('evo.db', fn() => new Legacy\Database());
        $this->app->singleton('evo.deprecated', fn() => new Legacy\DeprecatedCore());
        $this->app->singleton('evo.ManagerTheme', fn() => new Legacy\ManagerTheme());
    }

    /**
     * @return void
     */
    protected function defineConstants(): void
    {
        require __DIR__ . '/../helpers.php';
        require __DIR__ . '/../define.inc.php';
    }

    /**
     * @return void
     */
    protected function defineRoutes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::match(['get', 'post', 'path', 'delete'], '{any}', EvoController::class)
            ->middleware('web')
            ->where('any', '.*');
    }

    /**
     * @return void
     */
    protected function registerConfig(): void
    {
        if (!$this->app->configurationIsCached()) {
            Config::set('database.connections.' . Config::get('database.default') . '.prefix', env('DB_PREFIX', ''));
        }
    }
}
