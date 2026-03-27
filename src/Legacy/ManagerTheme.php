<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Legacy;

class ManagerTheme
{
    public function getActionId()
    {
        return null;
    }

    static public function getLexicon($key = null, $default = '')
    {
        return __('global.' . $key) ?? $default;
    }

    static public function getLang()
    {
        return __();
    }
}
