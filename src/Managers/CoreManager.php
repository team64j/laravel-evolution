<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Managers;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Team64j\LaravelEvolution\Models\SystemSetting;

class CoreManager
{
    public function __construct()
    {
        $this->loadConfig();
    }

    /**
     * @return void
     */
    protected function loadConfig(): void
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

    /**
     * @return bool
     */
    protected function checkSiteStatus(): bool
    {
        return Auth::getSession()->get('mgrValidated') || Config::get('global.site_status');
    }

    public function run()
    {
        if ($this->checkSiteStatus()) {
            dd(app('router')->current()->id);
        }

        return [];
    }
}
