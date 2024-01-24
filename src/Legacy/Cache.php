<?php

namespace Team64j\LaravelEvolution\Legacy;

use Team64j\LaravelEvolution\Models\SiteContent;
use Team64j\LaravelEvolution\Models\SiteHtmlSnippet;
use Team64j\LaravelEvolution\Models\SitePlugin;
use Team64j\LaravelEvolution\Models\SiteSnippet;
use Team64j\LaravelEvolution\Models\SystemEventname;
use Team64j\LaravelEvolution\Models\SystemSetting;
use Team64j\LaravelEvolution\Models\UserSetting;

/**
 * @class: synccache
 */
class Cache
{
    /**
     * @var string
     */
    public string $cachePath;

    /**
     * @var bool
     */
    public bool $showReport;

    /**
     * @var array
     */
    public array $aliases = [];

    /**
     * @var array
     */
    public array $parents = [];

    /**
     * @var array
     */
    public array $aliasVisible = [];

    /**
     * @var int
     */
    public mixed $request_time;

    /**
     * @var int
     */
    public int $cacheRefreshTime;

    /**
     * sync cache constructor.
     */
    public function __construct()
    {
        $this->request_time = $_SERVER['REQUEST_TIME'] + evo()->getConfig('server_offset_time');
    }

    /**
     * @param string $path
     */
    public function setCachePath(string $path): void
    {
        $this->cachePath = $path;
    }

    /**
     * @param bool $bool
     */
    public function setReport(bool $bool): void
    {
        $this->showReport = $bool;
    }

    /**
     * @param array|string $s
     *
     * @return array|string
     */
    public function escapeSingleQuotes(array|string $s): array|string
    {
        if ($s === '') {
            return $s;
        }

        $q1 = ["\\", "'"];
        $q2 = ["\\\\", "\\'"];

        return str_replace($q1, $q2, $s);
    }

    /**
     * @param array|string $s
     *
     * @return array|string
     */
    public function escapeDoubleQuotes(array|string $s): array|string
    {
        $q1 = ["\\", "\"", "\r", "\n", "\$"];
        $q2 = ["\\\\", "\\\"", "\\r", "\\n", "\\$"];

        return str_replace($q1, $q2, $s);
    }

    /**
     * @param int|string $id
     * @param string $path
     *
     * @return string
     */
    public function getParents(int|string $id, string $path = ''): string
    {
        // modx:returns child's parent
        if (empty($this->aliases)) {
            $result = SiteContent::query()
                ->where('deleted', 0)
                ->where('isfolder', 1)
                ->get([
                    'id',
                    'alias',
                    'parent',
                    'alias_visible',
                ])
                ->toArray();

            foreach ($result as $row) {
                if ($row['alias'] == '') {
                    $row['alias'] = $row['id'];
                }

                $this->aliases[$row['id']] = $row['alias'];
                $this->parents[$row['id']] = $row['parent'];
                $this->aliasVisible[$row['id']] = $row['alias_visible'];
            }
        }
        if (isset($this->aliases[$id])) {
            if ($this->aliasVisible[$id] == 1) {
                if ($path != '') {
                    $path = $this->aliases[$id] . '/' . $path;
                } else {
                    $path = $this->aliases[$id];
                }
            }

            return $this->getParents($this->parents[$id], $path);
        }

        return $path;
    }

    /**
     * @return void
     */
    public function emptyCache(): void
    {
        if (!isset($this->cachePath)) {
            evo()->getService('ExceptionHandler')->messageQuit("Cache path not set.");
        }

        \Illuminate\Support\Facades\Cache::flush();

        UserSetting::query()
            ->whereIn('setting_name', ['password', 'password_confirmation', 'clearPassword'])
            ->delete();
        $files = glob(realpath($this->cachePath) . '/*.pageCache.php');
        $filesincache = count($files);
        $deletedfiles = [];
        while ($file = array_shift($files)) {
            $name = basename($file);
            clearstatcache();
            if (is_file($file)) {
                if (unlink($file)) {
                    $deletedfiles[] = $name;
                }
            }
        }
        $opcache_restrict_api = trim(ini_get('opcache.restrict_api'));
        $opcache_restrict_api = $opcache_restrict_api && mb_stripos(__FILE__, $opcache_restrict_api) !== 0;

        if (!$opcache_restrict_api && function_exists('opcache_get_status')) {
            $opcache = opcache_get_status();
            if (!empty($opcache['opcache_enabled'])) {
                opcache_reset();
            }
        }

        $this->buildCache();

        $this->publishTimeConfig();

        // finished cache stuff.
        if ($this->showReport) {
            global $_lang;
            $total = count($deletedfiles);
            echo sprintf($_lang['refresh_cache'], $filesincache, $total);
            if ($total > 0) {
                if (isset($opcache)) {
                    echo '<p>Opcache empty.</p>';
                }
                echo '<p>' . $_lang['cache_files_deleted'] . '</p><ul>';
                foreach ($deletedfiles as $deletedfile) {
                    echo '<li>' . $deletedfile . '</li>';
                }
                echo '</ul>';
            }
        }
    }

    /**
     * @param int|string $cacheRefreshTime
     */
    public function publishTimeConfig(int|string $cacheRefreshTime = ''): void
    {
        $cacheRefreshTimeFromDB = $this->getCacheRefreshTime();
        if (!preg_match('@^[0-9]+$]@', $cacheRefreshTime) || $cacheRefreshTimeFromDB < $cacheRefreshTime) {
            $cacheRefreshTime = $cacheRefreshTimeFromDB;
        }

        // write the file
        $content = '<?php' . "\n";
        $content .= '$recent_update=\'' . $this->request_time . '\';' . "\n";
        $content .= '$cacheRefreshTime=\'' . $cacheRefreshTime . '\';' . "\n";

        $filename = evo()->getSitePublishingFilePath();
        if (!$handle = fopen($filename, 'w')) {
            exit("Cannot open file ($filename");
        }

        $content .= "\n";

        // Write $somecontent to our opened file.
        if (fwrite($handle, $content) === false) {
            exit("Cannot write publishing info file! Make sure the assets/cache directory is writable!");
        }
    }

    /**
     * @return int
     */
    public function getCacheRefreshTime(): int
    {
        // update publish time file
        $timesArr = [];

        $minpub = SiteContent::query()
            ->where('pub_date', '>', $this->request_time)->min('pub_date');

        if ($minpub != null) {
            $timesArr[] = $minpub;
        }

        $minpub = SiteContent::query()
            ->where('unpub_date', '>', $this->request_time)->min('unpub_date');

        if ($minpub != null) {
            $timesArr[] = $minpub;
        }

        if (!empty($this->cacheRefreshTime)) {
            $timesArr[] = $this->cacheRefreshTime;
        }

        if (count($timesArr) > 0) {
            $cacheRefreshTime = min($timesArr);
        } else {
            $cacheRefreshTime = 0;
        }

        return $cacheRefreshTime;
    }

    /**
     * build siteCache file
     *
     * @return boolean success
     */
    public function buildCache(): bool
    {
        $modx = evo();

        $content = "<?php\n";
        //$content = '';

        // SETTINGS & DOCUMENT LISTINGS CACHE

        // get settings
        $systemSettings = SystemSetting::all();
        $config = [];
        $content .= '$c=&$this->config;';
        foreach ($systemSettings as $systemSetting) {
            $content .= '$c[\'' . $systemSetting->setting_name . '\']="' .
                $this->escapeDoubleQuotes((string) $systemSetting->setting_value) . '";';
            $config[$systemSetting->setting_name] = $systemSetting->setting_value;
        }

        if (isset($config['enable_filter']) && $config['enable_filter'] == 1) {
            if (SitePlugin::activePhx()->count()) {
                $content .= '$this->config[\'enable_filter\']=\'0\';';
            }
        }

        // enabled = 1, disabled = 0, only folders = 2
        if (!isset($config['alias_listing']) || $config['alias_listing'] != 0) {
            // WRITE Aliases to cache file
            $result = SiteContent::query()
                ->where('deleted', 0)
                ->when(
                    isset($config['alias_listing']) && $config['alias_listing'] == 2,
                    fn($q) => $q->where('isfolder', 1)
                )
                ->get([
                    'id',
                    'alias',
                    'parent',
                    'isfolder',
                    'alias_visible',
                ])
                ->toArray();
            $content .= '$a=&$this->aliasListing;';
            $content .= '$d=&$this->documentListing;';
            $content .= '$m=&$this->documentMap;';
            foreach ($result as $doc) {
                $tmpPath = '';

                if ($doc['alias'] == '') {
                    $doc['alias'] = $doc['id'];
                }
                if ($config['friendly_urls'] && $config['use_alias_path']) {
                    $tmpPath = $this->getParents($doc['parent']);
                    $alias = (strlen($tmpPath) > 0 ? $tmpPath . '/' : '') . $doc['alias'];
                    $key = $alias;
                } else {
                    $key = $doc['alias'];
                }

                $doc['path'] = $tmpPath;

                // alias listing
                $content .= '$a[' . $doc['id'] . ']=array(\'id\'=>' . $doc['id'] . ',\'alias\'=>\'' . $doc['alias'] .
                    '\',\'path\'=>\'' . $doc['path'] . '\',\'parent\'=>' . $doc['parent'] . ',\'isfolder\'=>' .
                    $doc['isfolder'] . ',\'alias_visible\'=>' . $doc['alias_visible'] . ');';
                // document listing
                $content .= '$d[\'' . $key . '\']=' . $doc['id'] . ';';
                // document map
                $content .= '$m[]=array(' . $doc['parent'] . '=>' . $doc['id'] . ');';
            }
        }

        if (!isset($config['disable_chunk_cache']) || $config['disable_chunk_cache'] != 1) {
            // WRITE Chunks to cache file
            $chunks = SiteHtmlsnippet::all();
            $content .= '$c=&$this->chunkCache;';
            foreach ($chunks->toArray() as $doc) {
                $content .= '$c[\'' . $doc['name'] . '\']=\'' .
                    ($doc['disabled'] ? '' : $this->escapeSingleQuotes($doc['snippet'])) . '\';';
            }
        }

        if (!isset($config['disable_snippet_cache']) || $config['disable_snippet_cache'] != 1) {
            // WRITE snippets to cache file
            $snippets = SiteSnippet::query()
                ->select('site_snippets.*', 'site_modules.properties as sharedproperties')
                ->leftJoin('site_modules', 'site_snippets.moduleguid', '=', 'site_modules.guid')
                ->get();
            $content .= '$s=&$this->snippetCache;';
            foreach ($snippets->toArray() as $row) {
                if ($row['disabled']) {
                    $content .= '$s[\'' . $row['name'] . '\']=\'return false;\';';
                } else {
                    $value = trim($row['snippet']);
                    if ($modx->getConfig('minifyphp_incache')) {
                        $value = $this->php_strip_whitespace($value);
                    }
                    $content .= '$s[\'' . $row['name'] . '\']=\'' . $this->escapeSingleQuotes($value) . '\';';
                    $properties = $modx->parseProperties($row['properties']);
                    $sharedproperties = $modx->parseProperties($row['sharedproperties']);
                    $properties = array_merge($sharedproperties, $properties);
                    if (0 < count($properties)) {
                        $content .= '$s[\'' . $row['name'] . 'Props\']=\'' .
                            $this->escapeSingleQuotes(json_encode($properties)) . '\';';
                    }
                }
            }
        }

        if (!isset($config['disable_plugins_cache']) || $config['disable_plugins_cache'] != 1) {
            // WRITE plugins to cache file
            $plugins = SitePlugin::query()
                ->select('site_plugins.*', 'site_modules.properties as sharedproperties')
                ->leftJoin('site_modules', 'site_plugins.moduleguid', '=', 'site_modules.guid')
                ->where('site_plugins.disabled', 0)
                ->get();
            $content .= '$p=&$this->pluginCache;';
            foreach ($plugins->toArray() as $row) {
                $value = trim($row['plugincode']);
                if ($modx->getConfig('minifyphp_incache')) {
                    $value = $this->php_strip_whitespace($value);
                }
                $content .= '$p[\'' . $row['name'] . '\']=\'' . $this->escapeSingleQuotes($value) . '\';';
                if ($row['properties'] != '' || $row['sharedproperties'] != '') {
                    $properties = $modx->parseProperties($row['properties']);
                    $sharedproperties = $modx->parseProperties($row['sharedproperties']);
                    $properties = array_merge($sharedproperties, $properties);
                    if (0 < count($properties)) {
                        $content .= '$p[\'' . $row['name'] . 'Props\']=\'' .
                            $this->escapeSingleQuotes(json_encode($properties)) . '\';';
                    }
                }
            }
        }

        if (true) { // system events
            // WRITE system event triggers
            $systemEvents = SystemEventname::query()
                ->select(
                    'system_eventnames.name as evtname',
                    'site_plugin_events.pluginid',
                    'site_plugins.name as pname'
                )
                ->leftJoin('site_plugin_events', 'system_eventnames.id', '=', 'site_plugin_events.evtid')
                ->leftJoin('site_plugins', 'site_plugin_events.pluginid', '=', 'site_plugins.id')
                ->where('site_plugins.disabled', 0)
                ->orderBy('system_eventnames.name', 'ASC')
                ->orderBy('site_plugin_events.priority', 'ASC')
                ->get();
            $content .= '$e=&$this->pluginEvent;';
            $events = [];
            foreach ($systemEvents->toArray() as $row) {
                if (!isset($events[$row['evtname']])) {
                    $events[$row['evtname']] = [];
                }
                $events[$row['evtname']][] = $row['pname'];
            }
            foreach ($events as $evtname => $pluginnames) {
                $events[$evtname] = $pluginnames;
                $content .= '$e[\'' . $evtname . '\']=array(\'' .
                    implode('\',\'', $this->escapeSingleQuotes($pluginnames)) . '\');';
            }
        }

        $content .= "\n";

        // close and write the file
        $filename = $modx->getSiteCacheFilePath();

        // invoke OnBeforeCacheUpdate event
        $modx->invokeEvent('OnBeforeCacheUpdate');

//        \Illuminate\Support\Facades\Cache::forever($filename, $content);
        if (@file_put_contents($filename, $content) === false) {
            exit("Cannot write $filename! Make sure file or its directory is writable!");
        }

        if (is_dir(MODX_BASE_PATH . '/assets/cache') && !is_file(MODX_BASE_PATH . '/assets/cache/.htaccess')) {
            file_put_contents(
                MODX_BASE_PATH . '/assets/cache/.htaccess',
                "<ifModule mod_authz_core.c>\nRequire all denied\n</ifModule>\n<ifModule !mod_authz_core.c>\norder deny,allow\ndeny from all\n</ifModule>\n"
            );
        }

        // invoke OnCacheUpdate event
        $modx->invokeEvent('OnCacheUpdate');

        return true;
    }

    /**
     * @param string $source
     *
     * @return string
     *
     * @see http://php.net/manual/en/tokenizer.examples.php
     */
    // phpcs:ignore
    public function php_strip_whitespace(string $source): string
    {
        $source = trim($source);
        if (!str_starts_with($source, '<?php')) {
            $source = '<?php ' . $source;
        }

        $tokens = token_get_all($source);
        $_ = '';
        $prev_token = 0;
        $chars = explode(' ', '( ) ; , = { } ? :');
        foreach ($tokens as $token) {
            if (is_string($token)) {
                if (in_array($token, ['=', ':'])) {
                    $_ = trim($_);
                } elseif (in_array($token, ['(', '{']) && in_array($prev_token, [T_IF, T_ELSE, T_ELSEIF])) {
                    $_ = trim($_);
                }
                $_ .= $token;
                if ($prev_token == T_END_HEREDOC) {
                    $_ .= "\n";
                }
                continue;
            }

            [$type, $text] = $token;

            switch ($type) {
                case T_COMMENT:
                case T_DOC_COMMENT:
                    break;
                case T_WHITESPACE:
                    if ($prev_token != T_END_HEREDOC) {
                        $_ = trim($_);
                    }
                    $lastChar = substr($_, -1);
                    if (!in_array($lastChar, $chars)) { // ,320,327,288,284,289
                        if (!in_array(
                            $prev_token,
                            [T_FOREACH, T_WHILE, T_FOR, T_BOOLEAN_AND, T_BOOLEAN_OR, T_DOUBLE_ARROW]
                        )
                        ) {
                            $_ .= ' ';
                        }
                    }
                    break;
                case T_IS_EQUAL:
                case T_IS_IDENTICAL:
                case T_IS_NOT_EQUAL:
                case T_DOUBLE_ARROW:
                case T_BOOLEAN_AND:
                case T_BOOLEAN_OR:
                case T_START_HEREDOC:
                    if ($prev_token != T_START_HEREDOC) {
                        $_ = trim($_);
                    }
                    $prev_token = $type;
                    $_ .= $text;
                    break;
                default:
                    $prev_token = $type;
                    $_ .= $text;
            }
        }
        $source = preg_replace(
            ['@^<\?php@i', '|\s+|', '|<!--|', '|-->|', '|-->\s+<!--|'],
            ['', ' ', "\n" . '<!--', '-->' . "\n", '-->' . "\n" . '<!--'],
            $_
        );

        return trim($source);
    }
}
