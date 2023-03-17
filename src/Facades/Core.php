<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Facades;

use Illuminate\Support\Facades\Facade;
use Team64j\LaravelEvolution\Managers\CoreManager;

/**
 * @mixin CoreManager
 */
class Core extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'cms.core';
    }
}
