<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Providers;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Team64j\LaravelEvolution\Http\Controllers\Controller;
use Team64j\LaravelEvolution\Managers\CoreManager;
use Team64j\LaravelEvolution\Managers\UriManager;
use Team64j\LaravelEvolution\Models\User;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->registerAliases();

        $this->booted(function () {
            try {
                $this->app['router']->getRoutes()->match($this->app['request']);
            } catch (Exception $exception) {
                Config::set('auth.providers.users.model', User::class);

                $this->app['router']->addRoute(
                    $this->app['request']->getMethod(),
                    $this->app['request']->getPathInfo(),
                    [Controller::class, 'index']
                )->middleware(['web']);
            }
        });
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
