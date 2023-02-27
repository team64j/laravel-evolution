<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Providers;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Team64j\LaravelEvolution\Http\Controllers\Controller;
use Team64j\LaravelEvolution\Managers\CoreManager;
use Team64j\LaravelEvolution\Models\SystemSetting;

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
            $this->getConfig();

            $this->app->alias(CoreManager::class, 'core');

            $this->app['router']->addRoute(
                $this->app['request']->getMethod(),
                $this->app['request']->getPathInfo(),
                [Controller::class, 'index']
            )->middleware(['web']);
        }
    }

    /**
     * @return void
     */
    protected function getConfig(): void
    {
        $dbPrefix = \env('DB_PREFIX');

        if (!is_null($dbPrefix)) {
            Config::set('database.connections.mysql.prefix', $dbPrefix);
            Config::set('database.connections.pgsql.prefix', $dbPrefix);
        }

        Config::set(
            'global',
            (array) Cache::store('file')
                ->rememberForever(
                    'cms.settings',
                    fn() => SystemSetting::query()
                        ->pluck('setting_value', 'setting_name')
                        ->toArray()
                )
        );
    }
}
