<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Http\Controllers;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Routing\Controller;

class EvoController extends Controller
{
    /**
     * @return string|null
     * @throws BindingResolutionException
     */
    public function __invoke(): ?string
    {
        return evo()->executeParser();
    }
}
