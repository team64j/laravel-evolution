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
     * @param string $sep flatten a multidimensional array and combine keys with separator
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

if (!function_exists('get_by_key')) {
    /**
     * @param mixed $data
     * @param int|string $key
     * @param mixed|null $default
     * @param Closure|string|null $validate
     *
     * @return mixed
     */
    function get_by_key(mixed $data, int|string $key, mixed $default = null, Closure|string $validate = null): mixed
    {
        $out = $default;
        $found = false;
        if (is_array($data) && (is_int($key) || is_string($key)) && $key !== '') {
            if (array_key_exists($key, $data)) {
                $out = $data[$key];
                $found = true;
            } else {
                $offset = 0;
                do {
                    if (($pos = mb_strpos($key, '.', $offset)) > 0) {
                        $subData = get_by_key($data, mb_substr($key, 0, $pos));
                        $offset = $pos + 1;
                        $subKey = mb_substr($key, $offset);
                        if (is_array($subData) && array_key_exists($subKey, $subData)) {
                            $out = $subData[$subKey];
                            $found = true;
                            break;
                        }
                    } else {
                        break;
                    }
                } while (true);

                if ($found === false && ($pos = mb_strpos($key, '.', $offset)) > 0) {
                    $subData = get_by_key($data, mb_substr($key, 0, $pos));
                    $out = get_by_key($subData, mb_substr($key, $pos + 1), $default, $validate);
                }
            }
        }

        if ($found && $validate && is_callable($validate)) {
            if ($validate($out) === true) {
                return $out;
            }

            return $default;
        }

        return $out;
    }
}
