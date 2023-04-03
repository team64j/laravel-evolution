<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Facades;

use Illuminate\Support\Facades\Facade;
use Team64j\LaravelEvolution\Managers\UriManager;

/**
 * @method static array|null getCurrentRoute()
 * @method static string pathToUrl(string $path)
 * @method static array|null getRouteById(int $id = null)
 * @method static array|null getRouteByPath(string $path)
 * @method static array getParentsById(int $id, bool $current = false)
 *
 * @see UriManager
 */
class Uri extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'cms.uri';
    }
}
