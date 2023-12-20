<?php

namespace Team64j\LaravelEvolution\Legacy;

class DeprecatedCore
{
    /**
     * @deprecated
     *
     * return @void
     */
    public function dbConnect()
    {
        $modx = evo();
        $modx->getDatabase()->connect();
        $modx->rs = $modx->getDatabase()->conn;
    }

    /**
     * @param $sql
     *
     * @return bool|mysqli_result|resource
     * @deprecated
     *
     */
    public function dbQuery($sql)
    {
        return evo()->getDatabase()->query($sql);
    }

    /**
     * @param $rs
     *
     * @return int
     * @deprecated
     *
     */
    public function recordCount($rs)
    {
        return evo()->getDatabase()->getRecordCount($rs);
    }

    /**
     * @param $rs
     * @param string $mode
     *
     * @return array|bool|mixed|object|stdClass
     * @deprecated
     *
     */
    public function fetchRow($rs, $mode = 'assoc')
    {
        return evo()->getDatabase()->getRow($rs, $mode);
    }

    /**
     * @param $rs
     *
     * @return int
     * @deprecated
     *
     */
    public function affectedRows($rs)
    {
        return evo()->getDatabase()->getAffectedRows($rs);
    }

    /**
     * @param $rs
     *
     * @return int|mixed
     * @deprecated
     */
    public function insertId($rs): mixed
    {
        return evo()->getDatabase()->getInsertId($rs);
    }

    /**
     * @return void
     * @deprecated
     */
    public function dbClose(): void
    {
        evo()->getDatabase()->disconnect();
    }

    /**
     * @param array|string $array $array
     * @param string $root
     * @param string $prefix
     * @param string $type
     * @param bool $ordered
     * @param int $level
     *
     * @return string
     * @deprecated
     */
    public function makeList(
        array|string $array,
        string $root = 'root',
        string $prefix = 'sub_',
        string $type = '',
        bool $ordered = false,
        int $level = 0): string
    {
        // first find out whether the value passed is an array
        if (!is_array($array)) {
            return '<ul><li>Bad list</li></ul>';
        }

        if (!empty ($type)) {
            $attrs = " style='list-style-type: $type'";
        } else {
            $attrs = '';
        }

        $tabs = str_repeat("\t", $level);
        $html = $ordered ? $tabs . "<ol class='$root'$attrs>\n" : $tabs . "<ul class='$root'$attrs>\n";
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $html .= $tabs . "\t<li>" . $key . "\n" . $this->makeList(
                        $value,
                        $prefix . $root,
                        $prefix,
                        $type,
                        $ordered,
                        $level + 2
                    ) . $tabs . "\t</li>\n";
            } else {
                $html .= $tabs . "\t<li>" . $value . "</li>\n";
            }
        }
        $html .= $ordered ? $tabs . "</ol>\n" : $tabs . "</ul>\n";

        return $html;
    }

    /**
     * @return array
     * @deprecated
     */
    public function getUserData(): array
    {
        return [
            'ip' => $_SERVER['REMOTE_ADDR'],
            'ua' => $_SERVER['HTTP_USER_AGENT'],
        ];
    }

    /**
     * Returns true, install or interact when inside manager
     *
     * @return bool|string
     * @deprecated
     */
    public function insideManager(): bool|string
    {
        $m = false;
        if (defined('IN_MANAGER_MODE') && IN_MANAGER_MODE === true) {
            $m = true;
            if (defined('SNIPPET_INTERACTIVE_MODE') && SNIPPET_INTERACTIVE_MODE == 'true') {
                $m = "interact";
            } else {
                if (defined('SNIPPET_INSTALL_MODE') && SNIPPET_INSTALL_MODE == 'true') {
                    $m = "install";
                }
            }
        }

        return $m;
    }

    /**
     * @param $chunkName
     *
     * @return bool|string
     * @deprecated
     */
    public function putChunk($chunkName): bool|string
    {
        return evo()->getChunk($chunkName);
    }

    /**
     * @return array|string
     * @deprecated
     */
    public function getDocGroups(): array|string
    {
        return evo()->getUserDocGroups();
    }

    /**
     * @param string $o
     * @param string $n
     *
     * @return bool|string
     * @deprecated
     */
    public function changePassword(string $o, string $n): bool|string
    {
        return evo()->changeWebUserPassword($o, $n);
    }

    /**
     * @return array|bool
     * @deprecated
     */
    public function userLoggedIn(): bool|array
    {
        $modx = evo();
        $data = [];

        if ($modx->isFrontend() && isset ($_SESSION['webValidated'])) {
            // web user
            $data['loggedIn'] = true;
            $data['id'] = $_SESSION['webInternalKey'];
            $data['username'] = $_SESSION['webShortname'];
            $data['usertype'] = 'web'; // added by Raymond

            return $data;
        } else {
            if ($modx->isBackend() && isset ($_SESSION['mgrValidated'])) {
                // manager user
                $data['loggedIn'] = true;
                $data['id'] = $_SESSION['mgrInternalKey'];
                $data['username'] = $_SESSION['mgrShortname'];
                $data['usertype'] = 'manager'; // added by Raymond

                return $data;
            } else {
                return false;
            }
        }
    }

    /**
     * @param string $method
     * @param string $prefix
     * @param string $trim
     * @param string $REQUEST_METHOD
     *
     * @return array|bool
     * @deprecated
     */
    public function getFormVars(
        string $method = '',
        string $prefix = '',
        string $trim = '',
        string $REQUEST_METHOD = 'GET'): bool|array
    {
        $results = [];
        $method = strtoupper($method);
        if ($method == '') {
            $method = $REQUEST_METHOD;
        }
        if ($method == 'POST') {
            $method = &$_POST;
        } elseif ($method == 'GET') {
            $method = &$_GET;
        } else {
            return false;
        }

        reset($method);

        foreach ($method as $key => $value) {
            if (($prefix != '') && (substr($key, 0, strlen($prefix)) == $prefix)) {
                if ($trim) {
                    $pieces = explode($prefix, $key, 2);
                    $key = $pieces[1];
                    $results[$key] = $value;
                } else {
                    $results[$key] = $value;
                }
            } elseif ($prefix == '') {
                $results[$key] = $value;
            }
        }

        return $results;
    }

    /**
     * Displays a javascript alert message in the web browser
     *
     * @param string $msg Message to show
     * @param string $url URL to redirect to
     *
     * @deprecated
     */
    public function webAlert(string $msg, string $url = '')
    {
        $modx = evo();
        $msg = addslashes($modx->getDatabase()->escape($msg));

        if (str_starts_with(strtolower($url), 'javascript:')) {
            $act = '__WebAlert();';
            $fnc = 'function __WebAlert(){' . substr($url, 11) . '};';
        } else {
            $act = ($url ? "window.location.href='" . addslashes($url) . "';" : '');
        }
        $html = "<script>$fnc window.setTimeout(\"alert('$msg');$act\",100);</script>";
        if ($modx->isFrontend()) {
            $modx->regClientScript($html);
        } else {
            echo $html;
        }
    }
}
