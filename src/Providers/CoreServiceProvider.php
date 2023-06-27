<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Providers;

use Exception;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Team64j\LaravelEvolution\Http\Controllers\Controller;
use Team64j\LaravelEvolution\Managers\CoreManager;
use Team64j\LaravelEvolution\Managers\UriManager;
use Team64j\LaravelEvolution\Models\SystemSetting;
use Team64j\LaravelEvolution\Models\User;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfig();
        $this->registerAliases();

        $mgrDir = Config::get('cms.mgr_dir');

        if ($mgrDir && str_starts_with($this->app['request']->getPathInfo(), '/' . $mgrDir)) {
            return;
        }

        if (!$this->app->runningInConsole()) {
            $this->booted(function () {
                /** @var Router $router */
                $router = $this->app['router'];

                try {
                    $this->app['router']->getRoutes()->match($this->app['request']);
                } catch (Exception $exception) {
                    $this->registerConfig();

                    Config::set('auth.providers.users.model', User::class);

                    $router->any('{any}', [Controller::class, 'index'])
                        ->middleware('web')
                        ->where('any', '.*');
                }
            });
        }
    }

    /**
     * @return void
     */
    protected function mergeConfig(): void
    {
        $this->mergeConfigFrom(realpath(__DIR__ . '/../../config/cms.php'), 'cms');
    }

    /**
     * @return void
     */
    protected function registerConfig(): void
    {
        if (!Config::has('global')) {
            Config::set(
                'global',
                SystemSetting::query()
                    ->pluck('setting_value', 'setting_name')
                    ->toArray()
            );
        }
    }

    /**
     * @return void
     */
    protected function registerAliases(): void
    {
        $this->app->alias(CoreManager::class, 'cms.core');
        $this->app->alias(UriManager::class, 'cms.uri');
    }
}
