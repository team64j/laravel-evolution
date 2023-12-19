<?php

if (!function_exists('evolutionCMS')) {
    /**
     * @return DocumentParser
     */
    function evolutionCMS(): DocumentParser
    {
        return app('evo');
    }
}

if (!function_exists('evo')) {
    /**
     * @return DocumentParser
     */
    function evo(): DocumentParser
    {
        return app('evo');
    }
}

if (!function_exists('data_is_json')) {
    /**
     * @param $string
     * @param bool $returnData
     *
     * @return bool|mixed
     */
    function data_is_json($string, bool $returnData = false): mixed
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
        if (!$string || !str_contains($string, MODX_SANITIZE_SEED)) {
            return $string;
        }

        return str_replace(MODX_SANITIZE_SEED, '', $string);
    }
}

if (!function_exists('rename_key_arr')) {
    /**
     * Renaming array elements
     *
     * @param array $data
     * @param string $prefix
     * @param string $suffix
     * @param string $addPS separator prefix/suffix and array keys
     * @param string $sep flatten an multidimensional array and combine keys with separator
     *
     * @return array
     */
    function rename_key_arr(array $data, string $prefix = '', string $suffix = '', string $addPS = '.', string $sep = '.'): array
    {
        if ($prefix === '' && $suffix === '') {
            return $data;
        }

        $InsertPrefix = ($prefix !== '') ? $prefix . $addPS : '';
        $InsertSuffix = ($suffix !== '') ? $addPS . $suffix : '';
        $out = [];
        foreach ($data as $key => $item) {
            $key = $InsertPrefix . $key;
            $val = null;
            switch (true) {
                case is_scalar($item):
                    $val = $item;
                    break;
                case is_array($item):
                    $val = rename_key_arr($item, $key . $sep, $InsertSuffix, '', $sep);
                    $out = array_merge($out, $val);
                    $val = '';
                    break;
            }
            $out[$key . $InsertSuffix] = $val;
        }

        return $out;
    }
}
