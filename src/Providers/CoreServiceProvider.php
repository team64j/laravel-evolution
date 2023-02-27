<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Providers;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use Team64j\LaravelEvolution\Http\Controllers\Controller;
use Team64j\LaravelEvolution\Managers\CoreManager;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        if (str_starts_with($this->app['request']->getPathInfo(), '/' . config('cms.mgr_dir'))) {
            return;
        }

        try {
            $this->app['router']->getRoutes()->match($this->app['request']);
        } catch (Exception $exception) {
            $this->app->alias(CoreManager::class, 'core');

            $this->app['router']->addRoute(
                $this->app['request']->getMethod(),
                $this->app['request']->getPathInfo(),
                [Controller::class, 'index']
            )->middleware(['web']);
        }
    }
}
