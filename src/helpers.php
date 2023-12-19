<?php

use Team64j\LaravelEvolution\Evo;

if (!function_exists('evolutionCMS')) {
    /**
     * @return Evo
     */
    function evolutionCMS()
    {
        return app()->get('evo');
    }
}

if (!function_exists('evo')) {
    /**
     * @return Evo
     */
    function evo()
    {
        return app()->get('evo');
    }
}

if (!function_exists('data_is_json')) {
    /**
     * @param $string
     * @param bool $returnData
     *
     * @return bool|mixed
     */
    function data_is_json($string, bool $returnData = false)
    {
        $json = json_decode($string, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            return false;
        }

        if (!$returnData) {
            return true;
        }

        if (is_scalar($string)) {
            return $json;
        }

        return false;
    }
}

if (!function_exists('removeSanitizeSeed')) {
    /**
     * @param string $string
     *
     * @return string
     */
    function removeSanitizeSeed(string $string = ''): string
    {
        if (!$string || strpos($string, MODX_SANITIZE_SEED) === false) {
            return $string;
        }

        return str_replace(MODX_SANITIZE_SEED, '', $string);
    }
}
