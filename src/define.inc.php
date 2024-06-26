<?php

use Illuminate\Support\Str;

if (!defined('HTTPS_PORT')) {
    define('HTTPS_PORT', Config::get('evo.HTTPS_PORT') ?? '443'); //$https_port
}

if (!defined('SESSION_STORAGE')) {
    define('SESSION_STORAGE', Config::get('evo.SESSION_STORAGE') ?? 'default'); // $session_cookie_path
}

if (!defined('REDIS_HOST')) {
    define('REDIS_HOST', Config::get('evo.REDIS_HOST') ?? '127.0.0.1'); // $session_cookie_path
}

if (!defined('REDIS_PORT')) {
    define('REDIS_PORT', Config::get('evo.REDIS_PORT') ?? '6379'); // $session_cookie_path
}

if (!defined('SESSION_COOKIE_PATH')) {
    define('SESSION_COOKIE_PATH', Config::get('evo.SESSION_COOKIE_PATH') ?? ''); // $session_cookie_path
}

if (!defined('SESSION_COOKIE_DOMAIN')) {
    define('SESSION_COOKIE_DOMAIN', Config::get('evo.SESSION_COOKIE_DOMAIN') ?? ''); //$session_cookie_domain
}

if (!defined('MODX_CLASS')) {
    define('MODX_CLASS', Config::get('evo.MODX_CLASS') ?? '\DocumentParser');
}

if (!defined('MODX_SITE_HOSTNAMES')) {
    define('MODX_SITE_HOSTNAMES', Config::get('evo.MODX_SITE_HOSTNAMES') ?? '');
}

if (!defined('MGR_DIR')) {
    define('MGR_DIR', Config::get('evo.MGR_DIR') ?? 'manager');
}

if (!defined('EVO_CORE_PATH')) {
    define('EVO_CORE_PATH', Config::get('evo.EVO_CORE_PATH') ?? (dirname(__DIR__) . '/'));
}

if (!defined('EVO_STORAGE_PATH')) {
    define('EVO_STORAGE_PATH', Config::get('evo.EVO_STORAGE_PATH') ?? (EVO_CORE_PATH . 'storage/'));
}

if (!defined('EVO_CLI_USER')) {
    define('EVO_CLI_USER', Config::get('evo.EVO_CLI_USER') ?? 1);
}

if (!defined('MODX_BASE_PATH') || !defined('MODX_BASE_URL')) {
    // automatically assign base_path and base_url
    $script_name = str_replace(
        '\\',
        '/',
        dirname(
            $_SERVER[($_SERVER['PHP_SELF'] !== $_SERVER['SCRIPT_NAME'] &&
                ('undefined' === php_sapi_name() || app()->runningInConsole())) ?
                'PHP_SELF' : 'SCRIPT_NAME']
        )
    );

    if (substr($script_name, -1 - strlen(MGR_DIR)) === '/' . MGR_DIR ||
        str_contains($script_name, '/' . MGR_DIR . '/')
    ) {
        $separator = MGR_DIR;
    } elseif (str_contains($script_name, '/assets/')) {
        $separator = 'assets';
    } else {
        $separator = '';
    }

    if ($separator !== '') {
        $items = explode('/' . $separator, $script_name);
    } else {
        $items = [$script_name];
    }
    unset($script_name);

    if (count($items) > 1) {
        array_pop($items);
    }

    $url = implode($separator, $items);

    $base_url = Str::finish(implode($separator, $items), '/');
    unset($separator);

    reset($items);
    $items = explode('/' . MGR_DIR, str_replace('\\', '/', base_path()));
    if (count($items) > 1) {
        array_pop($items);
    }

    $base_path = Str::finish(
        str_replace('\\', '/', implode(MGR_DIR, $items))
        ,
        '/'
    );

    if (!defined('MODX_BASE_PATH')) {
        define('MODX_BASE_PATH', Config::get('evo.MODX_BASE_PATH') ?? $base_path);
    }
    unset($base_path);

    if (!defined('MODX_BASE_URL')) {
        define('MODX_BASE_URL', Config::get('evo.MODX_BASE_URL') ?? $base_url);
    }
    unset($base_url);
}

if (!preg_match('/\/$/', MODX_BASE_PATH)) {
    throw new RuntimeException('Please, use trailing slash at the end of MODX_BASE_PATH');
}

if (!preg_match('/\/$/', MODX_BASE_URL)) {
    throw new RuntimeException('Please, use trailing slash at the end of MODX_BASE_URL');
}

if (!defined('MODX_MANAGER_PATH')) {
    define('MODX_MANAGER_PATH', Config::get('evo.MODX_MANAGER_PATH') ?? (MODX_BASE_PATH . MGR_DIR . '/'));
}

$server_port = isset($_SERVER['HTTP_X_FORWARDED_PORT'])
    ? (int) $_SERVER['HTTP_X_FORWARDED_PORT'] : (int) ($_SERVER['SERVER_PORT'] ?? 80);

if (!defined('MODX_SITE_URL')) {
    // check for valid hostnames
    $site_hostname = 'localhost';
    if (!app()->runningInConsole()) {
        $site_hostname = str_replace(
            ':' . $server_port,
            '',
            $_SERVER['HTTP_HOST'] ?? $site_hostname
        );
    }
    $site_hostnames = explode(',', MODX_SITE_HOSTNAMES);
    if (!empty($site_hostnames[0]) && !in_array($site_hostname, $site_hostnames)) {
        $site_hostname = $site_hostnames[0];
    }
    unset($site_hostnames);

    // assign site_url
    if ((isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on') ||
        $server_port == HTTPS_PORT ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    ) {
        $site_url = 'https://' . $site_hostname;
    } else {
        $site_url = 'http://' . $site_hostname;
    }
    unset($site_hostname);

    if ($server_port !== 80) { // remove port from HTTP_HOST
        $site_url = str_replace(':' . $server_port, '', $site_url);
    }

    if (!in_array($server_port, [80, (int) HTTPS_PORT], true) &&
        strtolower($_SERVER['HTTPS'] ?? 'off')
    ) {
        $site_url .= ':' . $_SERVER['SERVER_PORT'];
    }

    $site_url .= MODX_BASE_URL;

    define('MODX_SITE_URL', Config::get('evo.MODX_SITE_URL') ?? $site_url);
    unset($site_url);
}

if (!preg_match('/\/$/', MODX_SITE_URL)) {
    throw new RuntimeException('Please, use trailing slash at the end of MODX_SITE_URL');
}

if (!defined('MODX_MANAGER_URL')) {
    define('MODX_MANAGER_URL', Config::get('evo.MODX_MANAGER_URL') ?? (MODX_SITE_URL . MGR_DIR . '/'));
}

if (!defined('MODX_SANITIZE_SEED')) {
    define('MODX_SANITIZE_SEED', 'sanitize_seed_' . base_convert(md5(__FILE__), 16, 36));
}

if (!defined('MODX_CLI') && app()->runningInConsole()) {
    define('MODX_CLI', true);
    if (!(defined('MODX_BASE_PATH') || defined('MODX_BASE_URL'))) {
        throw new RuntimeException('Please, define MODX_BASE_PATH and MODX_BASE_URL on cli mode');
    }

    if (!defined('MODX_SITE_URL')) {
        throw new RuntimeException('Please, define MODX_SITE_URL on cli mode');
    }
}
