<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Team64j\LaravelEvolution\Evo;
use Team64j\LaravelEvolution\Legacy;

class EvoServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->registerLegacyAliases();

        $this->app->singleton('evo', fn() => new Evo());
        $this->app->alias('evo', Evo::class);

        $this->app->singleton('evo.url', fn() => new Legacy\UrlProcessor());
        $this->app->alias('evo.url', Legacy\UrlProcessor::class);

        $this->app->singleton('evo.tpl', fn() => new Legacy\Parser());
        $this->app->alias('evo.tpl', Legacy\Parser::class);

        $this->app->singleton('evo.db', fn() => new Legacy\Database());
        $this->app->alias('evo.db', Legacy\Database::class);

        $this->app->singleton('evo.deprecated', fn() => new Legacy\DeprecatedCore());
        $this->app->alias('evo.deprecated', Legacy\DeprecatedCore::class);

        $this->app->singleton('evo.ManagerTheme', fn() => new Legacy\ManagerTheme());
        $this->app->alias('evo.ManagerTheme', Legacy\ManagerTheme::class);
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
    protected function defineConstants(): void
    {
        //require __DIR__ . '/../helpers.php';
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

        Route::match(['get', 'post', 'path', 'delete'], '{any}', [Evo::class, 'executeParser'])
            ->middleware('web')
            ->where('any', '.*');
    }

    /**
     * @return void
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/evo.php', 'evo');

        if (!$this->app->configurationIsCached()) {
            Config::set('database.connections.' . Config::get('database.default') . '.prefix', env('DB_PREFIX', ''));
        }
    }

    protected function registerLegacyAliases(): void
    {
        foreach (glob(__DIR__ . '/../Models/*') as $file) {
            $class = basename($file, '.php');
            class_alias('\Team64j\\LaravelEvolution\\Models\\' . $class, 'EvolutionCMS\\Models\\' . $class);
        }

        class_alias('\Team64j\\LaravelEvolution\\Evo', '\DocumentParser');
        class_alias('\Team64j\\LaravelEvolution\\Legacy\\Parser', '\DLTemplate');
        class_alias('\Team64j\\LaravelEvolution\\Legacy\\Event', '\SystemEvent');
    }
}
