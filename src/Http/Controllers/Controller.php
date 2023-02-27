<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Team64j\LaravelEvolution\Facades\Core;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {
        return Core::run();
    }
}
