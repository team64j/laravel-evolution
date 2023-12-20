<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Http\Controllers;

use Illuminate\Routing\Controller;

class EvoController extends Controller
{
    /**
     * @return mixed
     */
    public function __invoke()
    {
        return evo()->executeParser();
    }
}
