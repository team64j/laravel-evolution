<?php

declare(strict_types=1);

namespace EvolutionCMS\Legacy;

class ManagerTheme
{
    public function getActionId(): null
    {
        return null;
    }

    static public function getLexicon($key = null, $default = '')
    {
        return __('global.' . $key) ?? $default;
    }

    static public function getLang(): array | string | null
    {
        return __();
    }
}
