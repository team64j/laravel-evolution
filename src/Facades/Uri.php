<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Facades;

use Illuminate\Support\Facades\Facade;
use Team64j\LaravelEvolution\Managers\UriManager;

/**
 * @mixin UriManager
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
