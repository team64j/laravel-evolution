<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Http\Controllers;

use Illuminate\Routing\Controller;

class EvoController extends Controller
{
    /**
     * @return string|null
     */
    public function __invoke(): ?string
    {
        if (!defined('IN_INSTALL_MODE')) {
            define('IN_INSTALL_MODE', false);
        }

        if (!defined('IN_PARSER_MODE')) {
            define('IN_PARSER_MODE', false);
        }

        if (!defined('MODX_API_MODE')) {
            define('MODX_API_MODE', false);
        }

        if (!defined('IN_MANAGER_MODE')) {
            define('IN_MANAGER_MODE', false);
        }

        return evo()->executeParser();
    }
}
