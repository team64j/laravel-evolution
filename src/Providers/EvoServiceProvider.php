<?php

declare(strict_types=1);

namespace EvolutionCMS\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use EvolutionCMS\Evo;
use EvolutionCMS\Legacy;

class EvoServiceProvider extends ServiceProvider
{
    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function register(): void
    {
        $_SESSION = Legacy\Session::make(session()->all());

        $this->registerLegacyAliases();
        $this->bladeDirectives();

        $this->app->singleton('evo', fn() => new Evo());
        $this->app->alias('evo', Evo::class);

        $this->app->singleton('evo.url', fn() => new Legacy\UrlProcessor());
        $this->app->singleton('UrlProcessor', fn() => new Legacy\UrlProcessor());
        $this->app->alias('evo.url', Legacy\UrlProcessor::class);

        $this->app->singleton('HelperProcessor', fn() => new Legacy\HelperProcessor());

        $this->app->singleton('evo.tpl', fn() => new Legacy\Parser());
        $this->app->alias('evo.tpl', Legacy\Parser::class);

        $this->app->singleton('evo.cache', fn() => new Legacy\Cache());
        $this->app->alias('evo.cache', Legacy\Cache::class);

        $this->app->singleton('evo.db', fn() => new Legacy\Database());
        $this->app->alias('evo.db', Legacy\Database::class);

        $this->app->singleton('evo.deprecated', fn() => new Legacy\DeprecatedCore());
        $this->app->alias('evo.deprecated', Legacy\DeprecatedCore::class);

        //$this->app->singleton('evo.ManagerTheme', fn() => new Legacy\ManagerTheme());
        //$this->app->alias('evo.ManagerTheme', Legacy\ManagerTheme::class);

        register_shutdown_function([$this, 'registerShutdown']);

        //        $this->app->singleton('evo.auth', fn() => new \EvolutionCMS\Facades\AuthServices());
        //        $this->app->alias('evo.auth', \EvolutionCMS\Facades\AuthServices::class);
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

        Route::middleware('web')
            ->match(['get', 'post', 'path', 'delete'], '{any}', [Evo::class, 'executeParser'])
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
        class_alias('\EvolutionCMS\Evo', '\DocumentParser');
        class_alias('\EvolutionCMS\Legacy\Parser', '\DLTemplate');
        class_alias('\EvolutionCMS\Legacy\Event', '\SystemEvent');
        class_alias('\EvolutionCMS\Legacy\ManagerTheme', '\ManagerTheme');
        class_alias('\EvolutionCMS\Facades\UrlProcessor', '\UrlProcessor');
        class_alias('\EvolutionCMS\Providers\ServiceProvider', '\EvolutionCMS\ServiceProvider');
        class_alias('\EvolutionCMS\Legacy\TemplateController', '\EvolutionCMS\TemplateController');
    }

    protected function registerShutdown(): void
    {
        session()->save();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function bladeDirectives(): void
    {
        $directives = require __DIR__ . '/../../config/view.php';

        if (is_array($directives)) {
            foreach ($directives as $name => $callback) {
                $this->app->get('blade.compiler')->directive($name, $callback);
            }
        }

        Blade::if('auth', function (string $context = 'web') {
            return EvolutionCMS()->getLoginUserID($context) !== false;
        });

        Blade::if('guest', function (string $context = 'web') {
            return EvolutionCMS()->getLoginUserID($context) === false;
        });
    }
}
