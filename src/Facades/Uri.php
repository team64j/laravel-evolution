<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Facades;

use Illuminate\Support\Facades\Facade;

class Uri extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'uri';
    }
}
