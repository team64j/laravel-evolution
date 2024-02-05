<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use stdClass;
use Team64j\LaravelEvolution\Legacy\DeprecatedCore;
use Team64j\LaravelEvolution\Legacy\Event;
use Team64j\LaravelEvolution\Models\DocumentGroup;
use Team64j\LaravelEvolution\Models\DocumentgroupName;
use Team64j\LaravelEvolution\Models\EventLog;
use Team64j\LaravelEvolution\Models\SiteContent;
use Team64j\LaravelEvolution\Models\SitePlugin;
use Team64j\LaravelEvolution\Models\SiteSnippet;
use Team64j\LaravelEvolution\Models\SiteTemplate;
use Team64j\LaravelEvolution\Models\SiteTmplvar;
use Team64j\LaravelEvolution\Models\User;

class Evo
{
    use Traits\Settings {
        getSettings as loadConfig;
    }

    public Event $event;
    public int|string $documentIdentifier = 0;
    public string $documentOutput;
    public string $documentContent;
    public array $documentObject;
    public int $minParserPasses = 2;
    public int $maxParserPasses = 10;
    public array $chunkCache = [];
    public array $snippetCache = [];
    public array $pluginCache = [];
    protected string $documentMethod;
    protected mixed $time;
    protected string $q;
    protected string $systemCacheKey = '';
    protected int $documentGenerated;
    protected array $sjscripts = [];
    protected array $jscripts = [];
    protected float $tstart = 0;
    protected mixed $recentUpdate;
    protected array $pluginsTime = [];
    protected string $queryCode = '';
    protected array $snippetsTime = [];
    protected string $snippetsCode = '';
    protected string $pluginsCode = '';
    protected bool $useConditional = false;
    protected float $queryTime = 0;
    protected int $forwards = 3;
    protected string $cacheKey;
    protected array $tmpCache = [];
    protected string $documentName;
    protected bool $dumpPlugins = false;
    protected bool $dumpSnippets = false;
    protected bool $dumpSQL = false;
    protected bool $debug = false;
    protected array $placeholders = [];
    protected array $aliases = [];
    protected int $snipLapCount = 0;
    protected array $dataForView = [];
    protected mixed $currentSnippet;
    protected array $evolutionProperty = [
        'db' => 'getDatabase',
    ];

    /**
     * @var array
     * @deprecated
     */
    public array $documentListing = [];

    /**
     * @var array
     * @deprecated
     */
    public array $aliasListing = [];

    /**
     * @var string
     * @deprecated
     */
    public string $virtualDir = '';

    /**
     * @var Event
     * @deprecated
     */
    public Event $Event;

    public function __construct()
    {
        app()->singleton('evo.url', fn() => new Legacy\UrlProcessor());
        app()->singleton('evo.tpl', fn() => new Legacy\Parser());
        app()->singleton('evo.db', fn() => new Legacy\Database());
        app()->singleton('evo.deprecated', fn() => new Legacy\DeprecatedCore());
        app()->singleton('evo.ManagerTheme', fn() => new Legacy\ManagerTheme());

        $this->initialize();
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function getService($name): mixed
    {
        return $this->get($name);
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function get($name): mixed
    {
        return app('evo.' . $name);
    }

    /**
     * @param string $name
     *
     * @return mixed|void|null
     */
    public function __get(string $name)
    {
        if ($this->hasEvolutionProperty($name)) {
            if ($this->getConfig('error_reporting') > 99) {
                trigger_error(
                    'Property EvolutionCMS\Core::$' . $name . ' is deprecated and should no longer be used. ',
                    E_USER_DEPRECATED
                );
            }

            return $this->getEvolutionProperty($name);
        }
    }

    /**
     * @param string $method_name
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call(string $method_name, array $arguments = [])
    {
        $old = $this->getDeprecatedCore();
        if (method_exists($old, $method_name)) {
            if ($this->getConfig('error_reporting') > 99) {
                trigger_error(
                    'The EvolutionCMS\Core::' . $method_name . '() method is deprecated and should no longer be used. ',
                    E_USER_DEPRECATED
                );
            }

            return call_user_func_array([$old, $method_name], $arguments);
        }

        trigger_error(
            'The EvolutionCMS\Core::' . $method_name . '() method is undefined',
            E_USER_ERROR
        );
    }

    /**
     * @param string $property
     *
     * @return bool
     */
    public function hasEvolutionProperty(string $property): bool
    {
        return isset($this->evolutionProperty[$property]);
    }

    /**
     * @param string $property
     *
     * @return null|mixed
     */
    public function getEvolutionProperty(string $property): mixed
    {
        $abstract = Arr::get($this->evolutionProperty, $property);

        return $abstract === null ? null : $this->$abstract();
    }

    /**
     * @return DeprecatedCore
     * @deprecated
     */
    public function getDeprecatedCore(): DeprecatedCore
    {
        return app('evo.deprecated');
    }

    /**
     * @return mixed
     * @deprecated
     */
    public function getMail(): mixed
    {
        return $this->getService('MODxMailer');
    }

    /**
     * @return mixed
     * @deprecated
     */
    public function getModifiers(): mixed
    {
        return $this->getService('MODIFIERS');
    }

    /**
     * @return mixed
     * @deprecated
     */
    public function getPhpCompat(): mixed
    {
        return $this->getService('PHPCOMPAT');
    }

    /**
     * @param $tbl
     *
     * @return string
     * @deprecated
     */
    public function getFullTableName($tbl): string
    {
        return $this->getDatabase()->getFullTableName($tbl);
    }

    /**
     * @deprecated use UrlProcessor::makeUrl()
     */
    public function makeUrl($id, $alias = '', $args = '', $scheme = '')
    {
        return app('evo.url')->makeUrl((int) $id, $alias, $args, $scheme);
    }

    /**
     * @param array $data
     */
    public function addDataToView(array $data = []): void
    {
        $this->dataForView = array_merge($this->dataForView, $data);
    }

    /**
     * @return array
     */
    public function getDataForView(): array
    {
        return $this->dataForView;
    }

    /**
     * @return string
     */
    public function getSiteCacheFilePath(): string
    {
        return $this->getSiteCachePath('siteCache.idx.php');
    }

    /**
     * @param $key
     *
     * @return string
     */
    public function getHashFile($key): string
    {
        return $this->getSiteCachePath('docid_' . $key . '.pageCache.php');
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function getSiteCachePath(string $path = ''): string
    {
        return storage_path('framework/cache/data/' . $path);
    }

    /**
     * @return string
     */
    public function executeParser(): string
    {
        if (app()->runningInConsole()) {
            abort(500, 'Call DocumentParser::executeParser on CLI mode');
        }

        $this->_IIS_furl_fix(); // IIS friendly url fix

        // check site settings
        if ($this->checkSiteStatus()) {
            // make sure the cache doesn't need updating
            $this->updatePubStatus();

            // find out which document we need to display
            $this->documentMethod = Request::path() == '/' ? 'id' : 'alias';
            $this->documentIdentifier = $this->getDocumentIdentifier($this->documentMethod);
        } else {
            header('HTTP/1.0 503 Service Unavailable');
            $this->setSystemCacheKey('unavailable');
            if (!$this->config['site_unavailable_page']) {
                // display offline message
                $this->documentContent = $this->getConfig('site_unavailable_message');

                return $this->outputContent();
            }

            // setup offline page document settings
            $this->documentMethod = 'id';
            $this->documentIdentifier = $this->getConfig('site_unavailable_page');
        }

        if ($this->documentMethod !== 'alias') {
            app('evo.tpl')->setTemplatePath('views/');

            //$this->_fixURI();

            // invoke OnWebPageInit event
            $this->invokeEvent('OnWebPageInit');

            // invoke OnLogPageView event
            if ($this->getConfig('track_visitors')) {
                $this->invokeEvent('OnLogPageHit');
            }

            if ($this->getConfig('seostrict')) {
                $this->sendStrictURI();
            }

            return $this->prepareResponse();
        }

        $this->documentIdentifier = $this->cleanDocumentIdentifier($this->documentIdentifier);

        if ((bool) $this->getConfig('friendly_alias_urls') == 1) {
            // Check use_alias_path and check if $this->virtualDir is set to anything, then parse the path
            if ((bool) $this->getConfig('use_alias_path') == 1) {
                // use_alias_path = Use Friendly URL alias path = YES
                $virtualDir = app('evo.url')->virtualDir;
                $alias = ($virtualDir != '' ? $virtualDir . '/' : '') . $this->documentIdentifier;

                if (isset(app('evo.url')->documentListing[$alias])) {
                    $this->documentIdentifier = app('evo.url')->documentListing[$alias];
                } else {
                    $parent = $virtualDir ? app('evo.url')->getIdFromAlias($virtualDir) : 0;

                    if ($parent === null) {
                        $this->sendErrorPage();
                    }

                    $doc = SiteContent::query()
                        ->select('id')
                        ->where('parent', $parent)
                        ->where('alias', $this->documentIdentifier)
                        ->first();

                    if (is_null($doc)) {
                        $hidden = Cache::rememberForever('hidden_aliases', function () {
                            return SiteContent::query()
                                ->select('id', 'parent')
                                ->where('alias_visible', 0)
                                ->where('isfolder', 1)
                                ->pluck('parent', 'id')->toArray();
                        });

                        $docs = SiteContent::query()
                            ->select('id', 'parent')
                            ->where('alias', $this->documentIdentifier)
                            ->get();

                        $found = false;

                        foreach ($docs as $doc) {
                            $tmp_parent = $doc->parent;
                            while (isset($hidden[$tmp_parent])) {
                                $tmp_parent = $hidden[$tmp_parent];
                            }
                            if ($parent == $tmp_parent) {
                                $found = true;
                                break;
                            }
                        }

                        if (is_null($doc) || !$found) {
                            return $this->sendErrorPage();
                        }
                    }

                    $this->documentIdentifier = $doc->getKey();
                }
            } else {
                // use_alias_path = Use Friendly URL alias path = NO
                if (isset(app('evo.url')->documentListing[$this->documentIdentifier])) {
                    $this->documentIdentifier =
                        app('evo.url')->documentListing[$this->documentIdentifier];
                } else {
                    $doc = SiteContent::query()
                        ->select('id')
                        ->where('alias', $this->documentIdentifier)
                        ->first();

                    if (is_null($doc)) {
                        $this->sendErrorPage();
                    }

                    $this->documentIdentifier = $doc->getKey();
                }
            }
        }

        $this->documentMethod = 'id';
        app('evo.tpl')->setTemplatePath('views/');

        //$this->_fixURI();

        // invoke OnWebPageInit event
        $this->invokeEvent('OnWebPageInit');

        // invoke OnLogPageView event
        if ($this->getConfig('track_visitors')) {
            $this->invokeEvent('OnLogPageHit');
        }

        if ($this->getConfig('seostrict')) {
            $this->sendStrictURI();
        }

        return $this->prepareResponse();
    }

    /**
     * @return void
     */
    public function initialize(): void
    {
        $this->saveConfig = $this->config;
        $this->config = $this->configCompatibility();

        // events
        $this->event = new Legacy\Event();
        $this->Event = &$this->event; // alias for backward compatibility
        $this->time = $_SERVER['REQUEST_TIME']; // for having global timestamp

        //$this->getService('ExceptionHandler');

        $this->checkAuth();
        $this->getSettings();
        $this->q = Request::getPathInfo();
    }

    /**
     * @param string $context
     *
     * @return void
     */
    public function checkAuth(string $context = ''): void
    {
        if (empty($context)) {
            $context = $this->getContext();
        }
        if ($this->getLoginUserID($context) !== false) {
            $result = $this->checkAccess($this->getLoginUserID($context));
            if ($result === false) {
                UserManager::logout();
                if (IN_MANAGER_MODE) {
                    $this->sendRedirect('/' . MGR_DIR);
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getContext(): string
    {
        if (empty($this->context)) {
            $out = $this->isFrontend() ? 'web' : 'mgr';
        } else {
            $out = $this->context;
        }

        return $out;
    }

    /**
     * @return bool
     */
    public function isFrontend(): bool
    {
        return !$this->isBackend();
    }

    /**
     * @return bool
     */
    public function isBackend(): bool
    {
        return defined('IN_MANAGER_MODE') && IN_MANAGER_MODE;
    }

    /**
     * @param string $context
     *
     * @return false|mixed
     */
    public function getLoginUserID(string $context = ''): mixed
    {
        if (app()->runningInConsole() && defined('EVO_CLI_USER')) {
            return EVO_CLI_USER;
        }

        $out = false;

        if (empty($context)) {
            $context = $this->getContext();
        }

        if (Session::has($context . 'Validated')) {
            $out = Session::get($context . 'Validated');
        }

        return $out;
    }

    /**
     * @param string $url
     * @param int $count_attempts
     * @param string $type
     * @param string $responseCode
     *
     * @return false|void
     */
    public function sendRedirect(
        string $url,
        int $count_attempts = 0,
        string $type = 'REDIRECT_HEADER',
        string $responseCode = '')
    {
        if (!$url) {
            return false;
        }

        if (Str::contains($url, "\n")) {
            abort(500, 'No newline allowed in redirect url.');
        }

        if ($count_attempts) {
            // append the redirect count string to the url
            $currentNumberOfRedirects = $_GET['err'] ?? 0;
            if ($currentNumberOfRedirects > 3) {
                $this->getService('ExceptionHandler')->messageQuit(
                    "Redirection attempt failed - please ensure the document you're trying to redirect to exists. <p>Redirection URL: <i>" .
                    $url . '</i></p>'
                );
                exit;
            }
            $url .= (Str::contains($url, '?') ? '&' : '?') . 'err=' . ($currentNumberOfRedirects + 1);
        }

        if ($type === 'REDIRECT_REFRESH') {
            header('Refresh: 0;URL=' . $url);
            exit;
        }

        if ($type === 'REDIRECT_META') {
            echo '<META HTTP-EQUIV="Refresh" CONTENT="0; URL=' . $url . '" />';
            exit;
        }

        if ($type === 'REDIRECT_JS') {
            echo sprintf("<script>window.location.href='%s';</script>", $url);
            exit;
        }

        if ($type && $type !== 'REDIRECT_HEADER') {
            return false;
        }

        if ($responseCode && Str::contains($responseCode, '30')) {
            header($responseCode);
        }

        header(
            str_starts_with($url, MODX_BASE_URL)
                ? 'Location: ' . MODX_SITE_URL . substr($url, strlen(MODX_BASE_URL))
                : 'Location: ' . $url
        );
        exit;
    }

    /**
     * @return void
     */
    public function _IIS_furl_fix(): void
    {
        if ($this->getConfig('friendly_urls') != 1) {
            return;
        }

        if (!Str::contains($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS')) {
            return;
        }

        $url = $_SERVER['QUERY_STRING'];
        $err = substr($url, 0, 3);
        if ($err !== '404' && $err !== '405') {
            return;
        }

        $k = array_keys($_GET);
        unset($_GET[$k[0]], $_REQUEST[$k[0]]);
        // remove 404,405 entry
        $qp = parse_url(str_replace(MODX_SITE_URL, '', substr($url, 4)));
        $_SERVER['QUERY_STRING'] = $qp['query'];
        if ($qp['query']) {
            parse_str($qp['query'], $qv);
            foreach ($qv as $n => $v) {
                $_REQUEST[$n] = $_GET[$n] = $v;
            }
        }
        $_SERVER['PHP_SELF'] = MODX_BASE_URL . $qp['path'];
        $this->q = $qp['path'];
    }

    /**
     * @return bool
     */
    public function checkSiteStatus(): bool
    {
        if ($this->getConfig('site_status')) {
            return true;
        }

        if ($this->isLoggedin() && $this->hasPermission('access_permissions', 'mgr')) {
            return true;
        }

        return false;
    }

    /**
     * @param string $context
     *
     * @return bool
     */
    public function isLoggedIn(string $context = 'mgr'): bool
    {
        $context = $context == 'mgr' ? 'mgr' : 'web';
        $_ = $context . 'Validated';

        return app()->runningInConsole() || Session::exists($_);
    }

    /**
     * @param $pm
     * @param string $context
     *
     * @return bool
     */
    public function hasPermission($pm, string $context = ''): bool
    {
        if (app()->runningInConsole()) {
            return true;
        }

        if (empty($context)) {
            $context = $this->getContext();
        }

        if (Session::get($context . 'Role') && Session::get($context . 'Role') == 1) {
            return true;
        }

        $state = false;
        $pms = Session::get($context . 'Permissions', []);
        if ($pms) {
            $state = isset($pms[$pm]) && (bool) $pms[$pm] === true;
        }

        return $state;
    }

    /**
     * @return void
     */
    public function updatePubStatus(): void
    {
        $cacheRefreshTime = 0;
        $recent_update = 0;
        if (file_exists($this->getSitePublishingFilePath())) {
            @include $this->getSitePublishingFilePath();
        }
        $this->recentUpdate = $recent_update;

        $timeNow = $_SERVER['REQUEST_TIME'] + $this->getConfig('server_offset_time');
        if ($timeNow < $cacheRefreshTime || $cacheRefreshTime == 0) {
            return;
        }

        // now, check for documents that need publishing
        $field = ['published' => 1, 'publishedon' => $timeNow];
        $where = "pub_date <= $timeNow AND pub_date!=0 AND published=0";
        $result_pub = SiteContent::query()->select('id')->whereRaw($where)->get();
        SiteContent::query()->whereRaw($where)->update($field);

        if ($result_pub->count() >= 1) { //Event unPublished doc
            foreach ($result_pub as $row_pub) {
                $this->invokeEvent("OnDocUnPublished", [
                    "docid" => $row_pub->id,
                ]);
            }
        }

        // now, check for documents that need un-publishing
        $field = ['published' => 0, 'publishedon' => 0];
        $where = "unpub_date <= $timeNow AND unpub_date!=0 AND published=1";

        $result_unpub = SiteContent::query()->select('id')->whereRaw($where)->get();

        SiteContent::query()->whereRaw($where)->update($field);

        if ($result_unpub->count() >= 1) { //Event unPublished doc
            foreach ($result_unpub as $row_unpub) {
                $this->invokeEvent("OnDocUnPublished", [
                    "docid" => $row_unpub->id,
                ]);
            }
        }

        $this->recentUpdate = $timeNow;

        $this->clearCache('full');
    }

    /**
     * @return string
     */
    public function getSitePublishingFilePath(): string
    {
        return storage_path('framework/cache/data/sitePublishing.idx.php');
    }

    /**
     * @param string $evtName
     * @param array $extParams
     *
     * @return array|false
     */
    public function invokeEvent(string $evtName, array $extParams = []): bool|array
    {
        if ($this->isSafemode()) {
            return false;
        }

        $results = [];

        if (!$evtName) {
            return false;
        }

        $out = app('events')->dispatch('evolution.' . $evtName, [$extParams]);
        if ($out === false) {
            return false;
        }

        if (is_array($out)) {
            foreach ($out as $result) {
                if ($result !== null) {
                    $results[] = $result;
                }
            }
        }

        if (!isset($this->pluginEvent[$evtName])) {
            return $results ?: false;
        }

        $this->storeEvent();

        foreach ($this->pluginEvent[$evtName] as $pluginName) {
            $eventtime = $this->dumpPlugins ? $this->getMicroTime() : 0;
            // reset event object
            $e = &$this->event;
            $e->_resetEventObject();
            $e->name = $evtName;
            $e->activePlugin = $pluginName;

            // get plugin code
            $_ = $this->getPluginCode($pluginName);
            $pluginCode = $_['code'];
            $pluginProperties = $_['props'];

            // load default params/properties
            $parameter = $this->parseProperties($pluginProperties);

            if (!empty($extParams)) {
                $parameter = array_merge($parameter, $extParams);
            }

            // eval plugin
            $this->evalPlugin($pluginCode, $parameter);

            if (class_exists('PHxParser')) {
                $this->setConfig('enable_filter', 0);
            }

            if ($this->dumpPlugins) {
                $eventtime = $this->getMicroTime() - $eventtime;
                $this->pluginsCode .= sprintf(
                    '<fieldset><legend><b>%s / %s</b> (%2.2f ms)</legend>',
                    $evtName,
                    $pluginName,
                    $eventtime * 1000
                );
                foreach ($parameter as $k => $v) {
                    $this->pluginsCode .= $k . ' => ' . print_r($v, true) . '<br>';
                }
                $this->pluginsCode .= '</fieldset><br />';
                $this->pluginsTime[$evtName . ' / ' . $pluginName] += $eventtime;
            }

            if ($this->event->getOutput() != '') {
                $results[] = $this->event->getOutput();
            }

            if (!$this->event->_propagate) {
                break;
            }
        }

        $this->restoreEvent();

        return $results;
    }

    /**
     * @return bool
     */
    public function isSafeMode(): bool
    {
        return defined('SAFE_MODE') && SAFE_MODE && $this->isBackend();
    }

    /**
     * @return Event
     */
    public function storeEvent(): Event
    {
        if ($this->event->activePlugin !== '') {
            $event = new Event;
            $event->setPreviousEvent($this->event);
            $this->event = $event;
            $this->Event = &$this->event;
        } else {
            $event = $this->event;
        }

        return $event;
    }

    /**
     * @return float
     */
    public function getMicroTime(): float
    {
        [$usec, $sec] = explode(' ', microtime());

        return ((float) $usec + (float) $sec);
    }

    /**
     * @param $pluginName
     *
     * @return array
     */
    public function getPluginCode($pluginName): array
    {
        $plugin = [];
        if (isset($this->pluginCache[$pluginName])) {
            $pluginCode = $this->pluginCache[$pluginName];
            $pluginProperties = $this->pluginCache[$pluginName . 'Props'] ?? '';
        } else {
            $plugin = SitePlugin::query()
                ->select('name', 'plugincode', 'properties')
                ->where('name', $pluginName)
                ->where('disabled', 0)
                ->first()
                ->toArray();

            if (!is_null($plugin)) {
                $pluginCode = $this->pluginCache[$plugin['name']] = $plugin['plugincode'];
                $pluginProperties = $this->pluginCache[$plugin['name'] . 'Props'] = $plugin['properties'];
            } else {
                $pluginCode = $this->pluginCache[$pluginName] = "return false;";
                $pluginProperties = '';
            }
        }
        $plugin['code'] = $pluginCode;
        $plugin['props'] = $pluginProperties;

        return $plugin;
    }

    /**
     * @param $propertyString
     * @param $elementName
     * @param $elementType
     *
     * @return array
     */
    public function parseProperties($propertyString, $elementName = null, $elementType = null): array
    {
        $property = [];

        if (is_scalar($propertyString)) {
            $propertyString = trim($propertyString);
            $propertyString = str_replace(['{}', '} {'], ['', ','], $propertyString);
            if (!empty($propertyString) && $propertyString !== '{}') {
                $jsonFormat = data_is_json($propertyString, true);
                // old format
                if ($jsonFormat === false) {
                    $props = explode('&', $propertyString);
                    foreach ($props as $prop) {
                        if (empty($prop)) {
                            continue;
                        }

                        if (!str_contains($prop, '=')) {
                            $property[trim($prop)] = '';
                            continue;
                        }

                        $_ = explode('=', $prop, 2);
                        $key = trim($_[0]);
                        $p = explode(';', trim($_[1]));
                        $value = match ($p[1]) {
                            'list', 'list-multi', 'checkbox', 'radio' => !isset($p[3]) ? '' : $p[3],
                            default => !isset($p[2]) ? '' : $p[2],
                        };
                        if (!empty($key)) {
                            $property[$key] = $value;
                        }
                    }
                    // new json-format
                } else {
                    if (!empty($jsonFormat)) {
                        foreach ($jsonFormat as $key => $row) {
                            if (!empty($key)) {
                                if (is_array($row)) {
                                    if (isset($row[0]['value'])) {
                                        $value = $row[0]['value'];
                                    }
                                } else {
                                    $value = $row;
                                }
                                if (isset($value) && $value !== '') {
                                    $property[$key] = $value;
                                }
                            }
                        }
                    }
                }
            }
        } elseif (is_array($propertyString)) {
            $property = $propertyString;
        }
        if (!empty($elementName) && !empty($elementType)) {
            $out = $this->invokeEvent('OnParseProperties', [
                'element' => $elementName,
                'type' => $elementType,
                'args' => $property,
            ]);
            if (is_array($out)) {
                $out = array_pop($out);
            }
            if (is_array($out)) {
                $property = $out;
            }
        }

        return $property;
    }

    /**
     * @param string $pluginCode
     * @param array $params
     *
     * @return void
     */
    public function evalPlugin(string $pluginCode, array $params): void
    {
        $modx = &$this;
        if (!is_object($modx->event)) {
            $modx->event = new stdClass();
        }

        $modx->event->params = &$params; // store params inside event object

        if (is_array($params)) {
            extract($params, EXTR_SKIP);
        }
        /* if uncomment incorrect work plugin, cant understend where use this code and for what?
        // This code will avoid further execution of plugins in case they cause a fatal-error. clearCache() will delete those locks to allow execution of locked plugins again.
        // Related to https://github.com/modxcms/evolution/issues/1130
        $lock_file_path = MODX_BASE_PATH . 'assets/cache/lock_' . str_replace(' ','-',strtolower($this->event->activePlugin)) . '.pageCache.php';
        if($this->isBackend()) {
        if(is_file($lock_file_path)) {
        $msg = sprintf("Plugin parse error, Temporarily disabled '%s'.", $this->event->activePlugin);
        $this->logEvent(0, 3, $msg, $msg);
        return;
        }
        elseif(stripos($this->event->activePlugin,'ElementsInTree')===false) touch($lock_file_path);
        }*/
        ob_start();
        eval($pluginCode);
        $msg = ob_get_clean();
        // When reached here, no fatal error occured so the lock should be removed.
        /*if(is_file($lock_file_path)) unlink($lock_file_path);*/
        $error_info = error_get_last();

        if ((0 < $this->getConfig('error_reporting')) && $msg && $error_info !== null &&
            $this->detectError($error_info['type'])
        ) {
            $msg = $msg === false ? 'ob_get_contents() error' : $msg;
            $this->getService('ExceptionHandler')->messageQuit(
                'PHP Parse Error',
                '',
                true,
                $error_info['type'],
                $error_info['file'],
                'Plugin',
                $error_info['message'],
                $error_info['line'],
                $msg
            );
            if ($this->isBackend()) {
                $this->event->alert(
                    'An error occurred while loading. Please see the event log for more information.<p>' . $msg . '</p>'
                );
            }
        } else {
            echo $msg;
        }
        unset($modx->event->params);
    }

    /**
     * @param int $error
     *
     * @return bool
     */
    public function detectError(int $error): bool
    {
        $detected = false;
        if ($this->getConfig('error_reporting') === 199 && $error) {
            $detected = true;
        } elseif ($this->getConfig('error_reporting') === 99 && ($error & ~E_USER_DEPRECATED)) {
            $detected = true;
        } elseif ($this->getConfig('error_reporting') === 2 && ($error & ~E_NOTICE & ~E_USER_DEPRECATED)) {
            $detected = true;
        } elseif ($this->getConfig('error_reporting') === 1 && ($error & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT)) {
            $detected = true;
        }

        return $detected;
    }

    /**
     * @return Event|null
     */
    public function restoreEvent(): ?Event
    {
        $event = $this->event->getPreviousEvent();
        if ($event) {
            unset($this->event);
            $this->event = $event;
            $this->Event = &$this->event;
        } else {
            $this->event->activePlugin = '';
        }

        return $event;
    }

    /**
     * @param string $type
     * @param bool $report
     *
     * @return void
     */
    public function clearCache(string $type = '', bool $report = false): void
    {
        $cache_dir = app()->bootstrapPath();
        $path = $this['config']['view.compiled'];

        if ($path) {
            foreach ($this['files']->glob("$path/*") as $view) {
                $this['files']->delete($view);
            }
        }

        if (is_array($type)) {
            foreach ($type as $_) {
                $this->clearCache($_, $report);
            }
        } elseif ($type === 'full') {
            $sync = new Legacy\Cache();
            $sync->setCachePath($cache_dir);
            $sync->setReport($report);
            $sync->emptyCache();
        } elseif (preg_match('@^[1-9]\d*$@', $type)) {
            $key = ($this->getConfig('cache_type') == 2) ? $this->makePageCacheKey($type) : $type;
            $file_name = "docid_" . $key . "_*.pageCache.php";
            $cache_path = $cache_dir . $file_name;
            $files = (array) (glob($cache_path) ?: []);
            $files[] = $cache_dir . "docid_" . $key . ".pageCache.php";
            foreach ($files as $file) {
                if (!is_file($file)) {
                    continue;
                }
                unlink($file);
            }
        } else {
            $files = glob($cache_dir . '*');
            foreach ($files as $file) {
                $name = basename($file);
                if (!str_contains($name, '.pageCache.php')) {
                    continue;
                }
                if (!is_file($file)) {
                    continue;
                }
                unlink($file);
            }
        }
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function makePageCacheKey($id): mixed
    {
        $hash = $id;
        $tmp = null;
        $params = Request::collect()->sortKeys()->toArray();
        $cacheKey = $this->getSystemCacheKey();
        if (!empty($cacheKey)) {
            $hash = $cacheKey;
        } else {
            if (!empty($params)) {
                $hash .= '_' . md5(http_build_query($params));
            }
        }
        $evtOut = $this->invokeEvent("OnMakePageCacheKey", ["hash" => &$hash, "id" => $id, 'params' => $params]);
        if (is_array($evtOut) && count($evtOut) > 0) {
            $tmp = array_pop($evtOut);
        }

        return empty($tmp) ? $hash : $tmp;
    }

    /**
     * @return string
     */
    public function getSystemCacheKey(): string
    {
        return $this->systemCacheKey;
    }

    /**
     * @param string $systemCacheKey
     *
     * @return void
     */
    public function setSystemCacheKey(string $systemCacheKey): void
    {
        $this->systemCacheKey = $systemCacheKey;
    }

    /**
     * @param string $method
     *
     * @return bool|float|int|mixed|string|void|null
     */
    public function getDocumentIdentifier(string $method)
    {
        // function to test the query and find the retrieval method
        if ($method === 'alias') {
            return Request::getPathInfo();
        }

        $id = Request::input('id');
        if ($id) {
            if (!preg_match('/^[1-9]\d*$/', $id)) {
                $this->sendErrorPage();
                exit;
            }

            return $id;
        }

        if (Str::contains($_SERVER['REQUEST_URI'], 'index.php/')) {
            $this->sendErrorPage();
            exit;
        }

        return $this->getConfig('site_start');
    }

    /**
     * @param bool $noEvent
     *
     * @return string
     */
    public function sendErrorPage(bool $noEvent = false): string
    {
        $this->setSystemCacheKey('notfound');
        if (!$noEvent) {
            // invoke OnPageNotFound event
            $this->invokeEvent('OnPageNotFound');
        }
        $url = app('evo.url')->getNotFoundPageId();

        return $this->sendForward($url, 'HTTP/1.0 404 Not Found');
    }

    /**
     * @param $id
     * @param string $responseCode
     *
     * @return string
     */
    public function sendForward($id, string $responseCode = ''): string
    {
        if ($this->forwards <= 0) {
            $this->getService('ExceptionHandler')->messageQuit("Internal Server Error id=$id");
            header('HTTP/1.0 500 Internal Server Error');
            die('<h1>ERROR: Too many forward attempts!</h1><p>The request could not be completed due to too many unsuccessful forward attempts.</p>');
        }
        $this->forwards--;
        $this->documentIdentifier = $id;
        $this->documentMethod = 'id';
        if ($responseCode) {
            header($responseCode);
        }

        return $this->prepareResponse();
    }

    /**
     * @return string
     */
    public function prepareResponse(): string
    {
        // we now know the method and identifier, let's check the cache

        if ($this->getConfig('enable_cache') == 2 && $this->isLoggedIn()) {
            $this->setConfig('enable_cache', 0);
        }

        $this->documentContent = '';
        if ($this->getConfig('enable_cache')) {
            $this->documentContent = $this->getDocumentContentFromCache($this->documentIdentifier, true);
        }

        $this->documentGenerated = 0;
        $template = false;
        if ($this->documentContent == '') {
            // get document object from DB
            $this->documentObject =
                $this->getDocumentObjectFromCache($this->documentMethod, $this->documentIdentifier, 'prepareResponse');

            // write the documentName to the object
            $this->documentName = &$this->documentObject['pagetitle'];

            // check if we should not hit this document
            if ($this->documentObject['hide_from_tree'] == 1) {
                $this->setConfig('track_visitors', 0);
            }

            if ($this->documentObject['deleted'] == 1) {
                $this->sendErrorPage();
            } elseif ($this->documentObject['published'] == 0) { // validation routines
                $this->_sendErrorForUnpubPage();
            } elseif ($this->documentObject['type'] === 'reference') {
                $this->_sendRedirectForRefPage($this->documentObject['content']);
            }

            $template = app('evo.tpl')->getBladeDocumentContent();

            if ($template) {
                $this->documentObject['cacheable'] = 0;
                if (isset($this->documentObject['id'])) {
                    $data = [
                        'modx' => $this,
                        'documentObject' => $this->makeDocumentObject($this->documentObject['id']),
                    ];
                } else {
                    $data = [
                        'modx' => $this,
                        'documentObject' => [],
                        'siteContentObject' => [],
                    ];
                }

                view()->share($data);

                if ($this->isChunkProcessor('DLTemplate')) {
                    app('evo.tpl')->getBlade()->share($data);
                }

                $tpl = view()->make($template, $this->dataForView);
                $templateCode = $tpl->render();
            } else {
                // get the template and start parsing!
                if (!$this->documentObject['template']) {
                    $templateCode = '[*content*]';
                } else { // use blank template
                    $templateCode = app('evo.tpl')->getTemplateCodeFromDB($this->documentObject['template']);
                }

                if (str_starts_with($templateCode, '@INCLUDE')) {
                    $templateCode = $this->atBindInclude($templateCode);
                }
            }

            $this->documentContent = &$templateCode;

            // invoke OnLoadWebDocument event
            $this->invokeEvent('OnLoadWebDocument');

            if (!$template) {
                // Parse document source
                $this->documentContent = $this->parseDocumentSource($this->documentContent);
            }

            $this->documentGenerated = 1;
        }

        if ($this->getConfig('error_page') == $this->documentIdentifier) {
            if ($this->getConfig('error_page') != $this->getConfig('site_start')) {
                header('HTTP/1.0 404 Not Found');
            }
        }

        if ($template) {
            return $this->outputContent(false, false);
        } else {
            register_shutdown_function([& $this, 'postProcess']); // tell PHP to call postProcess when it shuts down

            return $this->outputContent();
        }
    }

    /**
     * @param $id
     * @param bool $loading
     *
     * @return string|void
     */
    public function getDocumentContentFromCache($id, bool $loading = false)
    {
        $key = ($this->getConfig('cache_type') == 2) ? $this->makePageCacheKey($id) : $id;
        if ($loading) {
            $this->cacheKey = (string) $key;
        }

        $cache_path = $this->getHashFile($key);

        if (!Cache::has($cache_path)) {
            $this->documentGenerated = 1;

            return '';
        }

        $content = Cache::get($cache_path);
        if (str_starts_with($content, '<?php')) {
            $content = substr($content, strpos($content, '?>') + 2);
        } // remove php header
        $a = explode('<!--__MODxCacheSpliter__-->', $content, 2);
        if (count($a) == 1) {
            $result = $a[0];
        } else { // return only document content
            $docObj = unserialize($a[0]); // rebuild document object
            // check page security
            if ($this->isFrontend() && $docObj['privateweb'] && isset($docObj['__MODxDocGroups__'])) {
                $pass = false;
                $usrGroups = $this->getUserDocGroups();
                $docGroups = explode(',', $docObj['__MODxDocGroups__']);
                // check is user has access to doc groups
                if (is_array($usrGroups)) {
                    foreach ($usrGroups as $v) {
                        if (!in_array($v, $docGroups)) {
                            continue;
                        }
                        $pass = true;
                        break;
                    }
                }
                // diplay error pages if user has no access to cached doc
                if (!$pass) {
                    if ($this->getConfig('unauthorized_page')) {
                        // check if file is not public
                        $documentGroups = DocumentGroup::query()->where('document', $id);
                        $total = $documentGroups->count();
                    } else {
                        $total = 0;
                    }

                    if ($total > 0) {
                        $this->sendUnauthorizedPage();
                    } else {
                        $this->sendErrorPage();
                    }

                    exit; // stop here
                }
            }

            // Grab the Scripts
            if (isset($docObj['__MODxSJScripts__'])) {
                $this->sjscripts = $docObj['__MODxSJScripts__'];
            }
            if (isset($docObj['__MODxJScripts__'])) {
                $this->jscripts = $docObj['__MODxJScripts__'];
            }

            // Remove intermediate variables
            unset($docObj['__MODxDocGroups__'], $docObj['__MODxSJScripts__'], $docObj['__MODxJScripts__']);

            $this->documentObject = $docObj;

            $result = $a[1]; // return document content
        }

        $this->documentGenerated = 0;
        // invoke OnLoadWebPageCache  event
        $this->documentContent = $result;
        $this->invokeEvent('OnLoadWebPageCache');

        return $result;
    }

    /**
     * @param bool $resolveIds
     *
     * @return array|mixed|string|void
     */
    public function getUserDocGroups(bool $resolveIds = false)
    {
        $context = $this->getContext();
        if (Session::get($context . 'Docgroups') && Session::get($context . 'Validated')) {
            $dg = Session::get($context . 'Docgroups');
            $dgn = Session::get($context . 'DocgrpNames', false);
        } else {
            $dg = '';
            $dgn = '';
        }

        if (!$resolveIds) {
            return $dg;
        }

        if (is_array($dgn)) {
            return $dgn;
        }

        if (is_array($dg)) {
            // resolve ids to names
            $dgn = [];
            $ds = DocumentgroupName::query()
                ->select('name')
                ->whereIn('id', $dg)
                ->get();

            foreach ($ds as $row) {
                $dgn[] = $row->name;
            }
            // cache docgroup names to session
            Session::put($context . 'DocgrpNames', $dgn);

            return $dgn;
        }
    }

    /**
     * @param bool $noEvent
     *
     * @return string
     */
    public function sendUnauthorizedPage(bool $noEvent = false): string
    {
        $_REQUEST['refurl'] = $this->documentIdentifier;
        $this->setSystemCacheKey('unauth');
        if (!$noEvent) {
            $this->invokeEvent('OnPageUnauthorized');
        }

        return $this->sendForward(app('evo.url')->getUnAuthorizedPageId(), 'HTTP/1.1 401 Unauthorized');
    }

    /**
     * @param $method
     * @param $identifier
     * @param string|null $isPrepareResponse
     *
     * @return array
     */
    public function getDocumentObjectFromCache($method, $identifier, string $isPrepareResponse = null): array
    {
        return Cache::rememberForever(
            __FUNCTION__ . $identifier,
            function () use ($method, $identifier, $isPrepareResponse) {
                return $this->getDocumentObject($method, $identifier, $isPrepareResponse);
            }
        );
    }

    /**
     * @param $method
     * @param $identifier
     * @param string|null $isPrepareResponse
     *
     * @return array
     */
    public function getDocumentObject($method, $identifier, string $isPrepareResponse = null): array
    {
        $cacheKey = md5(print_r(func_get_args(), true));

        if (isset($this->tmpCache[__FUNCTION__][$cacheKey])) {
            return $this->tmpCache[__FUNCTION__][$cacheKey];
        }

        // allow alias to be full path
        if ($method === 'alias') {
            $identifier = app('evo.url')->cleanDocumentIdentifier($identifier);
            $method = $this->documentMethod;
        }

        if ($method === 'alias' && $this->getConfig('use_alias_path') &&
            array_key_exists($identifier, app('evo.url')->documentListing)
        ) {
            $method = 'id';
            $identifier = app('evo.url')->documentListing[$identifier];
        }

        $out = $this->invokeEvent(
            'OnBeforeLoadDocumentObject',
            compact('method', 'identifier')
        );

        if (is_array($out) && is_array($out[0])) {
            $documentObject = $out[0];
        } else {
            // get document
            $documentObject = SiteContent::withoutProtected()
                ->where('site_content.' . $method, $identifier);
            $documentObject = $documentObject->first();
            if (is_null($documentObject)) {
                if ($this->getConfig('unauthorized_page')) {
                    // method may still be alias, while identifier is not full path alias, e.g. id not found above
                    if ($method === 'alias') {
                        $seclimit = DocumentGroup::query()
                            ->join('site_content')
                            ->where('document_groups.document', 'sc.id')
                            ->where('site_content.alias', DB::Raw($identifier))
                            ->exists();
                    } else {
                        $seclimit = DocumentGroup::query()
                            ->where('document', DB::Raw($identifier))
                            ->exists();
                    }
                    if ($seclimit) {
                        // match found but not publicly accessible, send the visitor to the unauthorized_page
                        $this->sendUnauthorizedPage();
                    } else {
                        $this->sendErrorPage();
                    }
                }
            }
            //this is now the document :)
            $documentObject = $documentObject->toArray();
            unset($documentObject['document_group'], $documentObject['document']);
            $documentObject['id'] = $identifier;

            if ($isPrepareResponse === 'prepareResponse') {
                $this->documentObject = &$documentObject;
            }

            $out = $this->invokeEvent(
                'OnLoadDocumentObject',
                compact('method', 'identifier', 'documentObject')
            );

            if (is_array($out) && is_array($out[0])) {
                $documentObject = $out[0];
            }

            if ($documentObject['template']) {
                // load TVs and merge with document - Orig by Apodigm - Docvars
                $tvs = SiteTmplvar::query()
                    ->select('site_tmplvars.*', 'site_tmplvar_contentvalues.value')
                    ->join('site_tmplvar_templates', 'site_tmplvar_templates.tmplvarid', '=', 'site_tmplvars.id')
                    ->leftJoin('site_tmplvar_contentvalues', function ($join) use ($documentObject) {
                        $join->on('site_tmplvar_contentvalues.tmplvarid', '=', 'site_tmplvars.id');
                        $join->on('site_tmplvar_contentvalues.contentid', '=', DB::raw((int) $documentObject['id']));
                    })
                    ->where('site_tmplvar_templates.templateid', $documentObject['template'])
                    ->get();

                $tmplvars = [];
                foreach ($tvs as $tv) {
                    $row = $tv->toArray();
                    if ($row['value'] == '') {
                        $row['value'] = $row['default_text'];
                    }

                    $tmplvars[$row['name']] = [
                        $row['name'],
                        $row['value'],
                        $row['display'],
                        $row['display_params'],
                        $row['type'],
                    ];
                }
                $documentObject = array_merge($documentObject, $tmplvars);

                $documentObject['templatealias'] = SiteTemplate::query()
                    ->select('templatealias')
                    ->firstWhere('id', $documentObject['template'])
                    ->templatealias;
            }
            $out = $this->invokeEvent(
                'OnAfterLoadDocumentObject',
                compact('method', 'identifier', 'documentObject')
            );

            if (is_array($out) && array_key_exists(0, $out) !== false && is_array($out[0])) {
                $documentObject = $out[0];
            }
        }
        $this->tmpCache[__FUNCTION__][$cacheKey] = $documentObject;

        return $documentObject;
    }

    /**
     * @param $qOrig
     *
     * @return int|string
     */
    public function cleanDocumentIdentifier($qOrig): int|string
    {
        return app('evo.url')->cleanDocumentIdentifier($qOrig, $this->documentMethod);
    }

    /**
     * @return void
     */
    public function _sendErrorForUnpubPage(): void
    {
        // Can't view unpublished pages !$this->checkPreview()
        if (!$this->hasPermission('view_unpublished', 'mgr') && !$this->hasPermission('view_unpublished')) {
            $this->sendErrorPage();

            return;
        }

        $udperms = new Legacy\Permissions();
        $udperms->user = $this->getLoginUserID();
        $udperms->document = $this->documentIdentifier;
        $udperms->role = Session::get('mgrRole');
        // Doesn't have access to this document
        if (!$udperms->checkPermissions()) {
            $this->sendErrorPage();
        }
    }

    /**
     * @param $url
     *
     * @return false|null
     */
    public function _sendRedirectForRefPage($url): ?bool
    {
        // check whether it's a reference
        if (preg_match('@^[1-9]\d*$@', $url)) {
            $url = app('evo.url')->makeUrl($url); // if it's a bare document id
        } elseif (Str::contains($url, '[~')) {
            $url = app('evo.url')->rewriteUrls($url); // if it's an internal docid tag, process it
        }

        return $this->sendRedirect($url, 0, '', 'HTTP/1.0 302 Moved Temporarily');
    }

    /**
     * @param $documentSource
     *
     * @return mixed
     */
    public function rewriteUrls($documentSource): mixed
    {
        return app('evo.url')->rewriteUrls($documentSource);
    }

    /**
     * @param int $id
     * @param bool $values
     *
     * @return array
     */
    public function makeDocumentObject(int $id, bool $values = true): array
    {
        if ($id == $this->documentObject['id']) {
            $documentObject = $this->documentObject;
            if ($values === true) {
                foreach ($documentObject as $key => $value) {
                    if (is_array($value)) {
                        $documentObject[$key] = $value[1] ?? '';
                    }
                }
            }

            return $documentObject;
        }

        $documentObject = SiteContent::query()->findOrFail($id)->toArray();
        if ($documentObject === null) {
            return [];
        }

        $rs = DB::table('site_tmplvars as tv')
            ->select('tv.*', 'tvc.value', 'tv.default_text')
            ->join('site_tmplvar_templates as tvtpl', 'tvtpl.tmplvarid', '=', 'tv.id')
            ->leftJoin('site_tmplvar_contentvalues as tvc', function ($join) use ($documentObject) {
                $join->on('tvc.tmplvarid', '=', 'tv.id');
                $join->on('tvc.contentid', '=', DB::raw((int) $documentObject['id']));
            })
            ->where('tvtpl.templateid', (int) $documentObject['template'])
            ->get();

        $tmplvars = [];
        foreach ($rs as $row) {
            if ($row->value == '') {
                $row->value = $row->default_text;
            }
            $tmplvars[$row->name] = [
                $row->name,
                $row->value,
                $row->display,
                $row->display_params,
                $row->type,
            ];
        }
        $documentObject = array_merge($documentObject, $tmplvars);
        if ($values === true) {
            foreach ($documentObject as $key => $value) {
                if (is_array($value)) {
                    $documentObject[$key] = $value[1] ?? '';
                }
            }
        }

        return $documentObject;
    }

    /**
     * @param $processor
     *
     * @return bool
     */
    public function isChunkProcessor($processor): bool
    {
        $value = (string) $this->getConfig('chunk_processor');
        if (is_object($processor)) {
            $processor = get_class($processor);
        }

        return is_scalar($processor) && mb_strtolower($value) === mb_strtolower($processor) &&
            class_exists($processor, false);
    }

    /**
     * @param string $str
     *
     * @return false|mixed|string
     */
    public function atBindInclude(string $str = ''): mixed
    {
        if (!str_starts_with($str, '@INCLUDE')) {
            return $str;
        }
        if (str_contains($str, "\n")) {
            $str = substr($str, 0, strpos("\n", $str));
        }

        $str = substr($str, 9);
        $str = trim($str);
        $str = str_replace('\\', '/', $str);
        $str = ltrim($str, '/');

        $tpl_dir = 'assets/templates/';

        if (str_starts_with($str, MODX_MANAGER_PATH)) {
            return false;
        }

        if (is_file(MODX_BASE_PATH . $str)) {
            $file_path = MODX_BASE_PATH . $str;
        } elseif (is_file(MODX_BASE_PATH . $tpl_dir . $str)) {
            $file_path = MODX_BASE_PATH . $tpl_dir . $str;
        } else {
            return false;
        }

        if (!$file_path || !is_file($file_path)) {
            return false;
        }

        ob_start();
        $modx = &$this;
        $result = include $file_path;
        if ($result === 1) {
            $result = '';
        }
        $content = ob_get_clean();
        if (!$content && $result) {
            $content = $result;
        }

        return $content;
    }

    /**
     * @param $source
     *
     * @return mixed|string
     */
    public function parseDocumentSource($source): mixed
    {
        // set the number of times we are to parse the document source
        $this->minParserPasses = !$this->minParserPasses ? 2 : $this->minParserPasses;
        $this->maxParserPasses = !$this->maxParserPasses ? 10 : $this->maxParserPasses;
        $passes = $this->minParserPasses;
        $st = null;

        for ($i = 0; $i < $passes; $i++) {
            // get source length if this is the final pass
            if ($i == ($passes - 1)) {
                $st = md5($source);
            }
            if ($this->dumpSnippets == 1) {
                $this->snippetsCode .= "<fieldset><legend><b style='color: #821517;'>PARSE PASS '.($i + 1).'</b></legend><p>The following snippets (if any) were parsed during this pass.</p>";
            }

            // invoke OnParseDocument event
            $this->documentOutput = $source; // store source code so plugins can
            $this->invokeEvent('OnParseDocument'); // work on it via $modx->documentOutput
            $source = $this->documentOutput;

            if ($this->getConfig('enable_at_syntax')) {
                $source = $this->ignoreCommentedTagsContent($source);
                $source = $this->mergeConditionalTagsContent($source);
            }

            $source = $this->mergeSettingsContent($source);
            $source = $this->mergeDocumentContent($source);
            $source = $this->mergeChunkContent($source);
            $source = $this->evalSnippets($source);
            $source = $this->mergePlaceholderContent($source);

            if ($this->dumpSnippets == 1) {
                $this->snippetsCode .= '</fieldset><br />';
            }
            if ($i == ($passes - 1) && $i < ($this->maxParserPasses - 1)) {
                // check if source content was changed
                if ($st != md5($source)) {
                    $passes++;
                } // if content change then increase passes because
            } // we have not yet reached maxParserPasses
        }

        return $source;
    }

    /**
     * @param string $content
     * @param string $left
     * @param string $right
     *
     * @return string
     */
    public function ignoreCommentedTagsContent(
        string $content,
        string $left = '<!--@-',
        string $right = '-@-->'): string
    {
        if (!Str::contains($content, $left)) {
            return $content;
        }

        $matches = $this->getTagsFromContent($content, $left, $right);
        if (!empty($matches)) {
            $addBreakMatches = [];
            foreach ($matches[0] as $i => $v) {
                $addBreakMatches[$i] = $v . "\n";
            }
            $content = str_replace($addBreakMatches, '', $content);
            if (Str::contains($content, $left)) {
                $content = str_replace($matches[0], '', $content);
            }
        }

        return $content;
    }

    /**
     * @param string $content
     * @param string $left
     * @param string $right
     *
     * @return array
     */
    public function getTagsFromContent(string $content, string $left = '[+', string $right = '+]'): array
    {
        $tags = [];
        $_ = $this->_getTagsFromContent($content, $left, $right);

        if (empty($_)) {
            return [];
        }

        foreach ($_ as $v) {
            $tags[0][] = "$left$v$right";
            $tags[1][] = $v;
        }

        return $tags;
    }

    /**
     * @param string $content
     * @param string $left
     * @param string $right
     *
     * @return array
     */
    public function _getTagsFromContent(string $content, string $left = '[+', string $right = '+]'): array
    {
        if (!Str::contains($content, $left)) {
            return [];
        }
        $spacer = md5('<<<EVO>>>');
        if ($left === '{{' && Str::contains($content, ';}}')) {
            $content = str_replace(';}}', ';}' . $spacer . '}', $content);
        }
        if ($left === '{{' && Str::contains($content, '{{}}')) {
            $content = str_replace('{{}}', sprintf('{%$1s{}%$1s}', $spacer), $content);
        }
        if ($left === '[[' && Str::contains($content, ']]]]')) {
            $content = str_replace(']]]]', ']]' . $spacer . ']]', $content);
        }
        if ($left === '[[' && Str::contains($content, ']]]')) {
            $content = str_replace(']]]', ']' . $spacer . ']]', $content);
        }

        $pos['<![CDATA['] = strpos($content, '<![CDATA[');
        $pos[']]>'] = strpos($content, ']]>');

        if ($pos['<![CDATA['] !== false && $pos[']]>'] !== false) {
            $content = substr($content, 0, $pos['<![CDATA[']) . substr($content, $pos[']]>'] + 3);
        }

        $lp = explode($left, $content);
        $piece = [];
        foreach ($lp as $lc => $lv) {
            if ($lc !== 0) {
                $piece[] = $left;
            }
            if (!Str::contains($lv, $right)) {
                $piece[] = $lv;
            } else {
                $rp = explode($right, $lv);
                foreach ($rp as $rc => $rv) {
                    if ($rc !== 0) {
                        $piece[] = $right;
                    }
                    $piece[] = $rv;
                }
            }
        }
        $lc = 0;
        $rc = 0;
        $fetch = '';
        $tags = [];
        foreach ($piece as $v) {
            if ($v === $left) {
                if (0 < $lc) {
                    $fetch .= $left;
                }
                $lc++;
            } elseif ($v === $right) {
                if ($lc === 0) {
                    continue;
                }
                $rc++;
                if ($lc === $rc) {
                    // #1200 Enable modifiers in Wayfinder - add nested placeholders to $tags like for $fetch = "phx:input=`[+wf.linktext+]`:test"
                    if ($this->config['enable_filter'] == 1 or class_exists('PHxParser')) {
                        if (Str::contains($fetch, $left)) {
                            $nested = $this->_getTagsFromContent($fetch, $left, $right);
                            foreach ($nested as $tag) {
                                if (!in_array($tag, $tags)) {
                                    $tags[] = $tag;
                                }
                            }
                        }
                    }

                    if (!in_array($fetch, $tags)) { // Avoid double Matches
                        $tags[] = $fetch; // Fetch
                    }
                    $fetch = ''; // and reset
                    $lc = 0;
                    $rc = 0;
                } else {
                    $fetch .= $right;
                }
            } elseif (0 < $lc) {
                $fetch .= $v;
            }
        }

        foreach ($tags as $i => $tag) {
            if (Str::contains($tag, $spacer)) {
                $tags[$i] = str_replace($spacer, '', $tag);
            }
        }

        return $tags;
    }

    /**
     * @param string $content
     * @param string $iftag
     * @param string $elseiftag
     * @param string $elsetag
     * @param string $endiftag
     *
     * @return string
     */
    public function mergeConditionalTagsContent(
        string $content,
        string $iftag = '<@IF:',
        string $elseiftag = '<@ELSEIF:',
        string $elsetag = '<@ELSE>',
        string $endiftag = '<@ENDIF>'
    ): string {
        if (Str::contains($content, '@IF')) {
            $content = $this->_prepareCTag($content, $iftag, $elseiftag, $elsetag, $endiftag);
        }

        if (!Str::contains($content, $iftag)) {
            return $content;
        }

        $sp = '#' . md5('ConditionalTags' . $_SERVER['REQUEST_TIME']) . '#';
        $content = str_replace(['<?php', '<?=', '<?', '?>'], ["{$sp}b", "{$sp}p", "{$sp}s", "{$sp}e"], $content);

        $pieces = explode('<@IF:', $content);
        foreach ($pieces as $i => $split) {
            if ($i === 0) {
                $content = $split;
                continue;
            }
            [$cmd, $text] = explode('>', $split, 2);
            $cmd = str_replace("'", "\'", $cmd);
            $content .= "<?php if(\$this->_parseCTagCMD('" . $cmd . "')): ?>";
            $content .= $text;
        }
        $pieces = explode('<@ELSEIF:', $content);
        foreach ($pieces as $i => $split) {
            if ($i === 0) {
                $content = $split;
                continue;
            }
            [$cmd, $text] = explode('>', $split, 2);
            $cmd = str_replace("'", "\'", $cmd);
            $content .= "<?php elseif(\$this->_parseCTagCMD('" . $cmd . "')): ?>";
            $content .= $text;
        }

        $content = str_replace(['<@ELSE>', '<@ENDIF>'], ['<?php else:?>', '<?php endif;?>'], $content);
        ob_start();
        eval('?>' . $content);
        $content = ob_get_clean();

        return str_replace(["{$sp}b", "{$sp}p", "{$sp}s", "{$sp}e"], ['<?php', '<?=', '<?', '?>'], $content);
    }

    /**
     * @param string $content
     * @param string $iftag
     * @param string $elseiftag
     * @param string $elsetag
     * @param string $endiftag
     *
     * @return string
     */
    public function _prepareCTag(
        string $content,
        string $iftag = '<@IF:',
        string $elseiftag = '<@ELSEIF:',
        string $elsetag = '<@ELSE>',
        string $endiftag = '<@ENDIF>'
    ): string {
        if (Str::contains($content, '<!--@IF ')) {
            $content = str_replace('<!--@IF ', $iftag, $content);
        } // for jp
        if (Str::contains($content, '<!--@IF:')) {
            $content = str_replace('<!--@IF:', $iftag, $content);
        }
        if (!Str::contains($content, $iftag)) {
            return $content;
        }
        if (Str::contains($content, '<!--@ELSEIF:')) {
            $content = str_replace('<!--@ELSEIF:', $elseiftag, $content);
        } // for jp
        if (Str::contains($content, '<!--@ELSE-->')) {
            $content = str_replace('<!--@ELSE-->', $elsetag, $content);
        } // for jp
        if (Str::contains($content, '<!--@ENDIF-->')) {
            $content = str_replace('<!--@ENDIF-->', $endiftag, $content);
        } // for jp
        if (Str::contains($content, '<@ENDIF-->')) {
            $content = str_replace('<@ENDIF-->', $endiftag, $content);
        }
        $tags = [$iftag, $elseiftag, $elsetag, $endiftag];

        return str_ireplace($tags, $tags, $content);
    }

    /**
     * @param string $content
     * @param array|null $ph
     *
     * @return string
     */
    public function mergeSettingsContent(string $content, array $ph = null): string
    {
        if ($this->getConfig('enable_at_syntax')) {
            if (stripos($content, '<@LITERAL>') !== false) {
                $content = $this->escapeLiteralTagsContent($content);
            }
        }

        if (!Str::contains($content, '[(')) {
            return $content;
        }

        if (empty($ph)) {
            $ph = array_merge(
                $this->allConfig(),
                [
                    'base_url' => MODX_BASE_URL,
                    'base_path' => MODX_BASE_PATH,
                    'site_url' => MODX_SITE_URL,
                    'valid_hostnames' => MODX_SITE_HOSTNAMES,
                    'site_manager_url' => MODX_MANAGER_URL,
                    'site_manager_path' => MODX_MANAGER_PATH,
                ]
            );
        }

        $matches = $this->getTagsFromContent($content, '[(', ')]');
        if (empty($matches)) {
            return $content;
        }

        foreach ($matches[1] as $i => $key) {
            [$key, $modifiers] = $this->splitKeyAndFilter($key);

            if (isset($ph[$key])) {
                $value = $ph[$key];
            } else {
                continue;
            }

            if ($modifiers !== false) {
                $value = $this->applyFilter($value, $modifiers, $key);
            }
            $s = &$matches[0][$i];
            if (Str::contains($content, $s)) {
                $content = str_replace($s, $value, $content);
            } elseif ($this->debug) {
                $this->addLog('mergeSettingsContent parse error', $_SERVER['REQUEST_URI'] . $s, 2);
            }
        }

        return $content;
    }

    /**
     * @param string $content
     * @param string $left
     * @param string $right
     *
     * @return string
     */
    public function escapeLiteralTagsContent(
        string $content,
        string $left = '<@LITERAL>',
        string $right = '<@ENDLITERAL>'): string
    {
        if (stripos($content, $left) === false) {
            return $content;
        }

        $matches = $this->getTagsFromContent($content, $left, $right);
        if (empty($matches)) {
            return $content;
        }

        [$sTags, $rTags] = $this->getTagsForEscape();
        foreach ($matches[1] as $i => $v) {
            $v = str_ireplace($sTags, $rTags, $v);
            $s = &$matches[0][$i];
            if (Str::contains($content, $s)) {
                $content = str_replace($s, $v, $content);
            } elseif ($this->debug) {
                $this->addLog('ignoreCommentedTagsContent parse error', $_SERVER['REQUEST_URI'] . $s, 2);
            }
        }

        return $content;
    }

    /**
     * @param string $tags
     *
     * @return array
     */
    public function getTagsForEscape(string $tags = '{{,}},[[,]],[!,!],[*,*],[(,)],[+,+],[~,~],[^,^]'): array
    {
        $srcTags = explode(',', $tags);
        $repTags = [];
        foreach ($srcTags as $tag) {
            $repTags[] = '\\' . $tag[0] . '\\' . $tag[1];
        }

        return [$srcTags, $repTags];
    }

    /**
     * @param string $title
     * @param string $msg
     * @param int $type
     *
     * @return void
     */
    public function addLog(string $title = 'no title', string $msg = '', int $type = 1): void
    {
        if ($title === '') {
            $title = 'no title';
        }
        if (is_array($msg)) {
            $msg = '<pre>' . print_r($msg, true) . '</pre>';
        } elseif ($msg === '') {
            $msg = $_SERVER['REQUEST_URI'];
        }
        $this->logEvent(0, $type, $msg, $title);
    }

    /**
     * @param int $evtid
     * @param int $type
     * @param string $msg
     * @param string $source
     *
     * @return void
     */
    public function logEvent(int $evtid, int $type, string $msg, string $source = 'Parser'): void
    {
        if (str_starts_with((string) DB::getConfig('charset'), 'utf8') && extension_loaded('mbstring')) {
            $esc_source = mb_substr($source, 0, 50, 'UTF-8');
        } else {
            $esc_source = substr($source, 0, 50);
        }

        $LoginUserID = $this->getLoginUserID();
        if ($LoginUserID == '') {
            $LoginUserID = 0;
        }

        // Types: 1 = information, 2 = warning, 3 = error
        if ($type < 1) {
            $type = 1;
        } elseif ($type > 3) {
            $type = 3;
        }

        EventLog::query()->insert([
            'eventid' => $evtid,
            'type' => $type,
            'createdon' => $_SERVER['REQUEST_TIME'] + $this->getConfig('server_offset_time'),
            'source' => $esc_source,
            'description' => $msg,
            'user' => $LoginUserID,
            'usertype' => $this->isFrontend() ? 1 : 0,
        ]);

        $this->invokeEvent('OnLogEvent', [
            'eventid' => $evtid,
            'type' => $type,
            'createdon' => $_SERVER['REQUEST_TIME'] + $this->getConfig('server_offset_time'),
            'source' => $esc_source,
            'description' => $msg,
            'user' => $LoginUserID,
            'usertype' => $this->isFrontend() ? 1 : 0,
        ]);

        if ($this->getConfig('send_errormail', '0') != '0') {
            if ($this->getConfig('send_errormail') <= $type) {
                $this->sendmail([
                    'subject' => 'Evolution CMS System Error on ' . $this->getConfig('site_name'),
                    'body' => 'Source: ' . $source .
                        ' - The details of the error could be seen in the Evolution CMS system events log.',
                    'type' => 'text',
                ]);
            }
        }
    }

    /**
     * @param array $params
     * @param string $msg
     * @param array $files
     *
     * @return mixed
     */
    public function sendmail(array $params = [], string $msg = '', array $files = []): mixed
    {
        $p = [];

        if (is_scalar($params)) {
            if (!Str::contains($params, '=')) {
                if (Str::contains($params, '@')) {
                    $p['to'] = $params;
                } else {
                    $p['subject'] = $params;
                }
            } else {
                $params_array = explode(',', $params);
                foreach ($params_array as $k => $v) {
                    $k = trim($k);
                    $v = trim($v);
                    $p[$k] = $v;
                }
            }
        } else {
            $p = $params;
        }
        if (isset($p['sendto'])) {
            $p['to'] = $p['sendto'];
        }

        if (isset($p['to']) && preg_match('@^\d+$@', $p['to'])) {
            $userinfo = $this->getUserInfo($p['to']);
            $p['to'] = $userinfo['email'];
        }
        if (isset($p['from']) && preg_match('@^\d+$@', $p['from'])) {
            $userinfo = $this->getUserInfo($p['from']);
            $p['from'] = $userinfo['email'];
            $p['fromname'] = $userinfo['username'];
        }
        if ($msg === '' && !isset($p['body'])) {
            $p['body'] = $_SERVER['REQUEST_URI'] . "\n" . $_SERVER['HTTP_USER_AGENT'] . "\n" . $_SERVER['HTTP_REFERER'];
        } elseif (is_string($msg) && 0 < strlen($msg)) {
            $p['body'] = $msg;
        }

        $sendto = !isset($p['to']) ? $this->getConfig('emailsender') : $p['to'];
        $sendto = explode(',', $sendto);
        $mail = $this->getMail();
        foreach ($sendto as $address) {
            [$name, $address] = $mail->address_split($address);
            $mail->AddAddress($address, $name);
        }
        if (isset($p['cc'])) {
            $p['cc'] = explode(',', $p['cc']);
            foreach ($p['cc'] as $address) {
                [$name, $address] = $mail->address_split($address);
                $mail->AddCC($address, $name);
            }
        }
        if (isset($p['bcc'])) {
            $p['bcc'] = explode(',', $p['bcc']);
            foreach ($p['bcc'] as $address) {
                [$name, $address] = $mail->address_split($address);
                $mail->AddBCC($address, $name);
            }
        }
        if (isset($p['from']) && Str::contains($p['from'], '<') && str_ends_with($p['from'], '>')) {
            [$p['fromname'], $p['from']] = $mail->address_split($p['from']);
        }
        $mail->setFrom(
            $p['from'] ?? $this->getConfig('emailsender'),
            $p['fromname'] ?? $this->getConfig('site_name')
        );
        $mail->Subject = (!isset($p['subject'])) ? $this->getConfig('emailsubject') : $p['subject'];
        $mail->Body = $p['body'];
        if (isset($p['type']) && $p['type'] === 'text') {
            $mail->IsHTML(false);
        }
        if (!is_array($files)) {
            $files = [];
        }
        foreach ($files as $name => $path) {
            if (!is_file($path) || !is_readable($path)) {
                $path = MODX_BASE_PATH . $path;
            }
            if (is_file($path) && is_readable($path)) {
                if (is_numeric($name)) {
                    $mail->AddAttachment($path);
                } else {
                    $mail->AddAttachment($path, $name);
                }
            }
        }

        return $mail->send();
    }

    /**
     * @param $uid
     *
     * @return array|bool
     */
    public function getUserInfo($uid): bool|array
    {
        if (isset($this->tmpCache[__FUNCTION__][$uid])) {
            return $this->tmpCache[__FUNCTION__][$uid];
        }

        $row = User::query()
            ->select('users.username', 'users.password', 'user_attributes.*')
            ->join('user_attributes', 'users.id', '=', 'user_attributes.internalKey')
            ->where('users.id', $uid)
            ->first();

        if (is_null($row)) {
            return $this->tmpCache[__FUNCTION__][$uid] = false;
        }

        $row = $row->toArray();

        if (!isset($row['usertype']) || !$row['usertype']) {
            $row['usertype'] = 'manager';
        }

        $this->tmpCache[__FUNCTION__][$uid] = $row;

        return $row;
    }

    /**
     * @param $key
     *
     * @return array
     */
    public function splitKeyAndFilter($key): array
    {
        if ($this->getConfig('enable_filter') && str_contains($key, ':') && stripos($key, '@FILE') !== 0) {
            [$key, $modifiers] = explode(':', $key, 2);
        } else {
            $modifiers = false;
        }

        $key = trim($key);
        if ($modifiers !== false) {
            $modifiers = trim($modifiers);
        }

        return [$key, $modifiers];
    }

    /**
     * @param string $value
     * @param string|null $modifiers
     * @param string $key
     *
     * @return string
     */
    public function applyFilter(string $value = '', string $modifiers = null, string $key = ''): string
    {
        if (!$modifiers || $modifiers === 'raw') {
            return $value;
        }

        return $this->getModifiers()->phxFilter($key, $value, trim($modifiers));
    }

    /**
     * @param string $content
     * @param bool $ph
     *
     * @return string
     */
    public function mergeDocumentContent(string $content, bool $ph = false): string
    {
        if ($this->getConfig('enable_at_syntax')) {
            if (stripos($content, '<@LITERAL>') !== false) {
                $content = $this->escapeLiteralTagsContent($content);
            }
        }

        if (!Str::contains($content, '[*')) {
            return $content;
        }

        if (!isset($this->documentIdentifier)) {
            return $content;
        }

        if (empty($this->documentObject)) {
            return $content;
        }

        if (!$ph) {
            $ph = $this->documentObject;
        }

        $matches = $this->getTagsFromContent($content, '[*', '*]');
        if (!$matches) {
            return $content;
        }

        foreach ($matches[1] as $i => $key) {
            if (Str::contains($key, '[+')) {
                continue;
            } // Allow chunk {{chunk?&param=`xxx`}} with [*tv_name_[+param+]*] as content
            if (str_starts_with($key, '#')) {
                $key = substr($key, 1);
            } // remove # for QuickEdit format

            [$key, $modifiers] = $this->splitKeyAndFilter($key);
            if (Str::contains($key, '@')) {
                [$key, $context] = explode('@', $key, 2);
            } else {
                $context = false;
            }

            // if(!isset($ph[$key]) && !$context) continue; // #1218 TVs/PHs will not be rendered if custom_meta_title is not assigned to template like [*custom_meta_title:ne:then=`[*custom_meta_title*]`:else=`[*pagetitle*]`*]
            if ($context) {
                $value = $this->_contextValue("$key@$context", $this->documentObject['parent']);
            } else {
                $value = $ph[$key] ?? '';
            }

            if (is_array($value)) {
                $value = getTVDisplayFormat($value[0], $value[1], $value[2], $value[3], $value[4]);
            }

            $s = &$matches[0][$i];
            if ($modifiers !== false) {
                $value = $this->applyFilter($value, $modifiers, $key);
            }

            if (Str::contains($content, $s)) {
                $content = str_replace($s, $value, $content);
            } elseif ($this->debug) {
                $this->addLog('mergeDocumentContent parse error', $_SERVER['REQUEST_URI'] . $s, 2);
            }
        }

        return $content;
    }

    /**
     * @param string $key
     * @param int|null $parent
     *
     * @return false|mixed|string
     */
    public function _contextValue(string $key, int $parent = null): mixed
    {
        if (preg_match('/@\d+\/u/', $key)) {
            $key = str_replace(['@', '/u'], ['@u(', ')'], $key);
        }
        [$key, $str] = explode('@', $key, 2);

        if (Str::contains($str, '(')) {
            [$context, $option] = explode('(', $str, 2);
        } else {
            [$context, $option] = [$str, false];
        }

        if ($option) {
            $option = trim($option, ')(\'"`');
        }

        switch (strtolower($context)) {
            case 'site_start':
                $docid = $this->getConfig('site_start');
                break;
            case 'parent':
            case 'p':
                $docid = $parent;
                if ($docid == 0) {
                    $docid = $this->getConfig('site_start');
                }
                break;
            case 'ultimateparent':
            case 'uparent':
            case 'up':
            case 'u':
                if (Str::contains($str, '(')) {
                    $top = substr($str, strpos($str, '('));
                    $top = trim($top, '()"\'');
                } else {
                    $top = 0;
                }
                $docid = $this->getUltimateParentId($this->documentIdentifier, $top);
                break;
            case 'alias':
                $str = substr($str, strpos($str, '('));
                $str = trim($str, '()"\'');
                $docid = app('evo.url')->getIdFromAlias($str);
                break;
            case 'prev':
                if (!$option) {
                    $option = 'menuindex,ASC';
                } elseif (!Str::contains($option, ',')) {
                    $option .= ',ASC';
                }
                [$by, $dir] = explode(',', $option, 2);
                $children = $this->getActiveChildren($parent, $by, $dir);
                $find = false;
                $prev = false;
                foreach ($children as $row) {
                    if ($row['id'] == $this->documentIdentifier) {
                        $find = true;
                        break;
                    }
                    $prev = $row;
                }
                if ($find) {
                    if (isset($prev[$key])) {
                        return $prev[$key];
                    }
                    $docid = $prev['id'];
                } else {
                    $docid = '';
                }
                break;
            case 'next':
                if (!$option) {
                    $option = 'menuindex,ASC';
                } elseif (!Str::contains($option, ',')) {
                    $option .= ',ASC';
                }
                [$by, $dir] = explode(',', $option, 2);
                $children = $this->getActiveChildren($parent, $by, $dir);
                $find = false;
                $next = false;
                foreach ($children as $row) {
                    if ($find) {
                        $next = $row;
                        break;
                    }
                    if ($row['id'] == $this->documentIdentifier) {
                        $find = true;
                    }
                }
                if ($find) {
                    if (isset($next[$key])) {
                        return $next[$key];
                    }
                    $docid = $next['id'];
                } else {
                    $docid = '';
                }
                break;
            default:
                $docid = $str;
        }
        if (preg_match('@^[1-9]\d*$@', $docid)) {
            $this->setSystemCacheKey('');
            $value = $this->getField($key, $docid);
        } else {
            $value = '';
        }

        return $value;
    }

    /**
     * @param int|string $id
     * @param int $height
     *
     * @return array
     */
    public function getParentIds(int|string $id, int $height = 10): array
    {
        $parents = [];
        while ($id && $height--) {
            $aliasListing = get_by_key(app('evo.url')->aliasListing, $id, [], 'is_array');
            $tmp = get_by_key($aliasListing, 'parent');

            $current_id = $id;

            if ($this->getConfig('alias_listing') != 1) {
                $id = $tmp ?? (int) SiteContent::query()->findOrNew($id)->parent;
            } else {
                $id = $tmp;
            }

            if ((int) $id === 0) {
                break;
            }
            $parents[$current_id] = (int) $id;
        }

        return $parents;
    }

    /**
     * @param int $id
     * @param int $top
     *
     * @return mixed
     */
    public function getUltimateParentId(int $id, int $top = 0): int
    {
        $i = 0;
        while ($id && $i < 20) {
            if ($top == app('evo.url')->aliasListing[$id]['parent']) {
                break;
            }
            $id = app('evo.url')->aliasListing[$id]['parent'];
            $i++;
        }

        return $id;
    }

    /**
     * @param int $id
     * @param string $sort
     * @param string $dir
     * @param string $fields
     * @param bool $checkAccess
     *
     * @return mixed
     */
    public function getActiveChildren(
        int $id = 0,
        string $sort = 'menuindex',
        string $dir = 'ASC',
        string $fields = 'id, pagetitle, description, parent, alias, menutitle',
        bool $checkAccess = true): mixed
    {
        $cacheKey = md5(print_r(func_get_args(), true));
        if (isset($this->tmpCache[__FUNCTION__][$cacheKey])) {
            return $this->tmpCache[__FUNCTION__][$cacheKey];
        }

        // modify field names to use sc. table reference
        $fields = array_filter(array_map('trim', explode(',', $fields)));
        foreach ($fields as $key => $value) {
            if (stristr($value, '.') === false) {
                $fields[$key] = 'site_content.' . $value;
            }
        }
        $content = SiteContent::query()
            ->select($fields)
            ->where('site_content.parent', $id)
            ->active()
            ->groupBy('site_content.id');
        if ($sort != '') {
            $sort = 'site_content.' . implode(',site_content.', array_filter(array_map('trim', explode(',', $sort))));
            $content = $content->orderBy($sort, $dir);
        }
        if ($checkAccess) {
            $content->withoutProtected();
        }
        // build query
        $resourceArray = $content->get()->toArray();
        $this->tmpCache[__FUNCTION__][$cacheKey] = $resourceArray;

        return $resourceArray;
    }

    /**
     * @param string $field
     * @param int $docid
     *
     * @return false|mixed
     */
    public function getField(string $field = 'content', int $docid = 0): mixed
    {
        if (empty($docid) && isset($this->documentIdentifier)) {
            $docid = $this->documentIdentifier;
        } elseif (!preg_match('@^\d+$@', (string) $docid)) {
            $docid = app('evo.url')->getIdFromAlias($docid);
        }

        if (empty($docid)) {
            return false;
        }

        $cacheKey = md5(print_r(func_get_args(), true));
        if (isset($this->tmpCache[__FUNCTION__][$cacheKey])) {
            return $this->tmpCache[__FUNCTION__][$cacheKey];
        }

        $doc = $this->getDocumentObject('id', $docid);
        if (is_array($doc[$field])) {
            $tvs = $this->getTemplateVarOutput([$field], $docid);
            $content = $tvs[$field];
        } else {
            $content = $doc[$field];
        }

        $this->tmpCache[__FUNCTION__][$cacheKey] = $content;

        return $content;
    }

    /**
     * @param array $idnames
     * @param int|null $docid
     * @param int $published
     * @param string $sep
     *
     * @return array|false
     */
    public function getTemplateVarOutput(
        array $idnames = [],
        int $docid = null,
        int $published = 1,
        string $sep = ''): bool|array
    {
        if (!$idnames) {
            return false;
        }

        $output = [];
        $vars = $idnames;

        if ((int) $docid > 0) {
            $docid = (int) $docid;
        } else {
            $docid = $this->documentIdentifier;
        }
        // remove sort for speed
        $result = $this->getTemplateVars($vars, '*', $docid, $published, '', '');

        if (!$result) {
            return false;
        }

        foreach ($result as $iValue) {
            $row = $iValue;

            if (!isset($row['id']) or !$row['id']) {
                $output[$row['name']] = $row['value'];
            } else {
                $output[$row['name']] = getTVDisplayFormat(
                    $row['name'],
                    $row['value'],
                    $row['display'],
                    $row['display_params'],
                    $row['type'],
                    $docid,
                    $sep
                );
            }
        }

        return $output;
    }

    /**
     * @param array $idnames
     * @param string $fields
     * @param int $docid
     * @param int $published
     * @param string $sort
     * @param string $dir
     * @param bool $checkAccess
     *
     * @return false|mixed|array
     */
    public function getTemplateVars(
        array $idnames = [],
        string $fields = '*',
        int $docid = 0,
        int $published = 1,
        string $sort = 'rank',
        string $dir = 'ASC',
        bool $checkAccess = true): mixed
    {
        static $cached = [];
        $cacheKey = md5(print_r(func_get_args(), true));
        if (isset($cached[$cacheKey])) {
            return $cached[$cacheKey];
        }
        $cached[$cacheKey] = false;

        if (!$idnames) {
            return false;
        }

        // get document record
        if (empty($docid)) {
            $docid = $this->documentIdentifier;
            $docRow = $this->documentObject;
        } else {
            $docRow = $this->getDocument($docid, '*', $published, 0, $checkAccess);

            if (!$docRow) {
                $cached[$cacheKey] = false;

                return false;
            }
        }
        $table = $this->getDatabase()->getFullTableName('site_tmplvars');
        // get user defined template variables
        if (!empty($fields) && (is_scalar($fields) || is_array($fields))) {
            if (is_scalar($fields)) {
                $fields = explode(',', $fields);
            }
            $fields = array_filter(array_map('trim', $fields), function ($value) {
                return $value !== 'value';
            });
        } else {
            $fields = ['*'];
        }
        $sort = ($sort == '')
            ? '' : $table . '.' . implode(',' . $table . '.', array_filter(array_map('trim', explode(',', $sort))));

        if ($idnames[0] === '*') {
            $query = $table . '.id<>0';
        } else {
            $query =
                (is_numeric($idnames[0]) ? $table . '.id' : $table . '.name') . " IN ('" . implode("','", $idnames) .
                "')";
        }

        $rs = SiteTmplvar::query()
            ->select($fields)
            ->selectRaw(
                " IF(" . $this->getDatabase()->getConfig('prefix') . "site_tmplvar_contentvalues.value != '', " .
                $this->getDatabase()->getConfig('prefix') . "site_tmplvar_contentvalues.value, " .
                $this->getDatabase()->getConfig('prefix') . "site_tmplvars.default_text) as value"
            )
            ->join('site_tmplvar_templates', 'site_tmplvar_templates.tmplvarid', '=', 'site_tmplvars.id')
            ->leftJoin('site_tmplvar_contentvalues', function ($join) use ($docid) {
                $join->on('site_tmplvar_contentvalues.tmplvarid', '=', 'site_tmplvars.id');
                $join->on('site_tmplvar_contentvalues.contentid', '=', DB::raw($docid));
            })
            ->whereRaw(
                $query . " AND " . $this->getDatabase()->getConfig('prefix') . "site_tmplvar_templates.templateid = '" .
                $docRow['template'] . "'"
            );
        if ($sort != '') {
            $rs = $rs->orderByRaw($sort);
        }
        $rs = $rs->get();

        $result = $rs->toArray();

        if (is_array($docRow)) {
            ksort($docRow);

            foreach ($docRow as $name => $value) {
                if ($idnames[0] === '*' || in_array($name, $idnames)) {
                    $result[] = compact('name', 'value');
                }
            }
        }

        $cached[$cacheKey] = $result;

        return $result;
    }

    /**
     * @param int $id
     * @param string $fields
     * @param int $published
     * @param int $deleted
     * @param bool $checkAccess
     *
     * @return false|mixed
     */
    public function getDocument(
        int $id = 0,
        string $fields = '*',
        int $published = 1,
        int $deleted = 0,
        bool $checkAccess = true): mixed
    {
        if ($id == 0) {
            return false;
        }

        $docs = $this->getDocuments([$id], $published, $deleted, $fields, '', '', '', 1, $checkAccess);

        return $docs[0] ?? false;
    }

    /**
     * @param array $ids
     * @param int $published
     * @param int $deleted
     * @param string $fields
     * @param string $where
     * @param string $sort
     * @param string $dir
     * @param int $limit
     * @param bool $checkAccess
     *
     * @return array
     */
    public function getDocuments(
        array $ids = [],
        int $published = 1,
        int $deleted = 0,
        string $fields = '*',
        string $where = '',
        string $sort = 'menuindex',
        string $dir = 'ASC',
        int $limit = 0,
        bool $checkAccess = true
    ): array {
        $cacheKey = md5(print_r(func_get_args(), true));
        if (isset($this->tmpCache[__FUNCTION__][$cacheKey])) {
            return $this->tmpCache[__FUNCTION__][$cacheKey];
        }

        $documentChildren = SiteContent::withTrashed()->whereIn('site_content.id', $ids);
        if ($published) {
            $documentChildren = $documentChildren->where('site_content.published', $published);
        }
        if ($deleted) {
            $documentChildren = $documentChildren->where('site_content.deleted', $deleted);
        }

        if (is_string($where) && $where != '') {
            $documentChildren = $documentChildren->whereRaw($where);
        } elseif (is_array($where)) {
            $documentChildren = $documentChildren->where($where);
        }

        $fields = array_filter(array_map('trim', explode(',', $fields)));
        foreach ($fields as $key => $value) {
            if (stristr($value, '.') === false) {
                $fields[$key] = 'site_content.' . $value;
            }
        }
        $documentChildren = $documentChildren->select($fields);

        // modify field names to use sc. table reference
        if ($sort != '') {
            $sort = 'site_content.' . implode(',site_content.', array_filter(array_map('trim', explode(',', $sort))));
            $documentChildren = $documentChildren->orderBy($sort, $dir);
        }

        if ($checkAccess) {
            $documentChildren->withoutProtected();
        }

        if ($limit) {
            $documentChildren = $documentChildren->take($limit);
        }
        $resourceArray = $documentChildren->get()->toArray();

        $this->tmpCache[__FUNCTION__][$cacheKey] = $resourceArray;

        return $resourceArray;
    }

    /**
     * @return Legacy\Database
     */
    public function getDatabase(): Legacy\Database
    {
        return app('evo.db');
    }

    /**
     * @param string $content
     * @param array|null $ph
     *
     * @return string
     */
    public function mergeChunkContent(string $content, array $ph = null): string
    {
        if ($this->getConfig('enable_at_syntax')) {
            if (Str::contains($content, '{{ ')) {
                $content = str_replace(['{{ ', ' }}'], ['\{\{ ', ' \}\}'], $content);
            }
            if (stripos($content, '<@LITERAL>') !== false) {
                $content = $this->escapeLiteralTagsContent($content);
            }
        }
        if (!Str::contains($content, '{{')) {
            return $content;
        }

        if (empty($ph)) {
            $ph = $this->chunkCache;
        }

        $matches = $this->getTagsFromContent($content, '{{', '}}');
        if (empty($matches)) {
            return $content;
        }

        foreach ($matches[1] as $i => $key) {
            $snip_call = $this->_split_snip_call($key);
            $key = $snip_call['name'];
            $params = $this->getParamsFromString($snip_call['params']);

            [$key, $modifiers] = $this->splitKeyAndFilter($key);

            if (!isset($ph[$key])) {
                $ph[$key] = $this->getChunk($key);
            }
            $value = $ph[$key];

            if (empty($value) && !stripos('[', $key)) {
                continue;
            }

            $value = $this->parseText($value, $params); // parse local scope placeholers for ConditionalTags
            $value = $this->mergePlaceholderContent($value, $params); // parse page global placeholers
            if ($this->getConfig('enable_at_syntax')) {
                $value = $this->mergeConditionalTagsContent($value);
            }
            $value = $this->mergeDocumentContent($value);
            $value = $this->mergeSettingsContent($value);
            $value = $this->mergeChunkContent($value);

            if ($modifiers !== false) {
                $value = $this->applyFilter($value, $modifiers, $key);
            }

            $s = &$matches[0][$i];
            if (Str::contains($content, $s)) {
                $content = str_replace($s, $value, $content);
            } elseif ($this->debug) {
                $this->addLog('mergeChunkContent parse error', $_SERVER['REQUEST_URI'] . $s, 2);
            }
        }

        return $content;
    }

    /**
     * @param $call
     *
     * @return array
     */
    private function _split_snip_call($call): array
    {
        $spacer = md5('dummy');
        if (Str::contains($call, ']]>')) {
            $call = str_replace(']]>', "]$spacer]>", $call);
        }

        $splitPosition = $this->_getSplitPosition($call);

        if ($splitPosition !== false) {
            $name = substr($call, 0, $splitPosition);
            $params = substr($call, $splitPosition + 1);
        } else {
            $name = $call;
            $params = '';
        }

        $snip['name'] = trim($name);
        if (Str::contains($params, $spacer)) {
            $params = str_replace("]$spacer]>", ']]>', $params);
        }
        $snip['params'] = ltrim($params, "?& \t\n");

        return $snip;
    }

    /**
     * @param $str
     *
     * @return bool|int
     */
    public function _getSplitPosition($str): bool|int
    {
        $closeOpt = false;
        $maybePos = false;
        $inFilter = false;
        $pos = false;
        $total = strlen($str);
        for ($i = 0; $i < $total; $i++) {
            $c = substr($str, $i, 1);
            $cc = substr($str, $i, 2);
            if (!$inFilter) {
                if ($c === ':') {
                    $inFilter = true;
                } elseif ($c === '?') {
                    $pos = $i;
                } elseif ($c === ' ') {
                    $maybePos = $i;
                } elseif ($c === '&' && $maybePos) {
                    $pos = $maybePos;
                } elseif ($c === "\n") {
                    $pos = $i;
                } else {
                    $pos = false;
                }
            } else {
                if ($cc == $closeOpt) {
                    $closeOpt = false;
                } elseif ($c == $closeOpt) {
                    $closeOpt = false;
                } elseif ($closeOpt) {
                    continue;
                } elseif ($cc === "('") {
                    $closeOpt = "')";
                } elseif ($cc === '("') {
                    $closeOpt = '")';
                } elseif ($cc === '(`') {
                    $closeOpt = '`)';
                } elseif ($c === '(') {
                    $closeOpt = ')';
                } elseif ($c === '?') {
                    $pos = $i;
                } elseif ($c === ' ' && !Str::contains($str, '?')) {
                    $pos = $i;
                } else {
                    $pos = false;
                }
            }
            if ($pos) {
                break;
            }
        }

        return $pos;
    }

    /**
     * @param string $string
     *
     * @return array|void
     */
    public function getParamsFromString(string $string = '')
    {
        if (empty($string)) {
            return [];
        }

        if (Str::contains($string, '&_PHX_INTERNAL_')) {
            $string = str_replace(['&_PHX_INTERNAL_091_&', '&_PHX_INTERNAL_093_&'], ['[', ']'], $string);
        }

        $_ = $this->documentOutput;
        $this->documentOutput = $string;
        $this->invokeEvent('OnBeforeParseParams');
        $string = $this->documentOutput;
        $this->documentOutput = $_;

        $params = [];
        $_tmp = $string;
        $_tmp = ltrim($_tmp, '?&');
        $temp_params = [];
        $key = '';
        $value = null;
        while ($_tmp !== '') {
            $bt = $_tmp;
            $char = substr($_tmp, 0, 1);
            $_tmp = substr($_tmp, 1);

            if ($char === '=') {
                $_tmp = trim($_tmp);
                $delim = substr($_tmp, 0, 1);
                if (in_array($delim, ['"', "'", '`'])) {
                    [$null, $value, $_tmp] = explode($delim, $_tmp, 3);
                    unset($null);

                    if (str_starts_with(trim($_tmp), '//')) {
                        $_tmp = strstr(trim($_tmp), "\n");
                    }
                    $i = 0;
                    while ($delim === '`' && !str_starts_with(trim($_tmp), '&') && 1 < substr_count($_tmp, '`')) {
                        [$inner, $outer, $_tmp] = explode('`', $_tmp, 3);
                        $value .= "`$inner`$outer";
                        $i++;
                        if (100 < $i) {
                            exit('The nest of values are hard to read. Please use three different quotes.');
                        }
                    }
                    if ($i && $delim === '`') {
                        $value = rtrim($value, '`');
                    }
                } elseif (Str::contains($_tmp, '&')) {
                    [$value, $_tmp] = explode('&', $_tmp, 2);
                    $value = trim($value);
                } else {
                    $value = $_tmp;
                    $_tmp = '';
                }
            } elseif ($char === '&') {
                if (trim($key) !== '') {
                    $value = '1';
                } else {
                    continue;
                }
            } elseif ($_tmp === '') {
                $key .= $char;
                $value = '1';
            } elseif ($key !== '' || trim($char) !== '') {
                $key .= $char;
            }

            if (isset($value)) {
                if (Str::contains($key, 'amp;')) {
                    $key = str_replace('amp;', '', $key);
                }
                $key = trim($key);
                if (Str::contains($value, '[!')) {
                    $value = str_replace(['[!', '!]'], ['[[', ']]'], $value);
                }
                $value = $this->mergeDocumentContent($value);
                $value = $this->mergeSettingsContent($value);
                $value = $this->mergeChunkContent($value);
                $value = $this->evalSnippets($value);
                if (!str_starts_with($value, '@CODE:')) {
                    $value = $this->mergePlaceholderContent($value);
                }

                $temp_params[][$key] = $value;

                $key = '';
                $value = null;

                $_tmp = ltrim($_tmp, " ,\t");
                if (str_starts_with($_tmp, '//')) {
                    $_tmp = strstr($_tmp, "\n");
                }
            }

            if ($_tmp === $bt) {
                $key = trim($key);
                if ($key !== '') {
                    $temp_params[][$key] = '';
                }
                break;
            }
        }

        foreach ($temp_params as $p) {
            $k = key($p);
            if (str_ends_with($k, '[]')) {
                $k = substr($k, 0, -2);
                $params[$k][] = current($p);
            } elseif (Str::contains($k, '[') && str_ends_with($k, ']')) {
                [$k, $subk] = explode('[', $k, 2);
                $subk = substr($subk, 0, -1);
                $params[$k][$subk] = current($p);
            } else {
                $params[$k] = current($p);
            }
        }

        return $params;
    }

    /**
     * @param string $content
     *
     * @return string
     */
    public function evalSnippets(string $content): string
    {
        if (!Str::contains($content, '[[')) {
            return $content;
        }

        $matches = $this->getTagsFromContent($content, '[[', ']]');

        if (empty($matches)) {
            return $content;
        }

        $this->snipLapCount++;
        if ($this->dumpSnippets) {
            $this->snippetsCode .= '<fieldset><legend><b style="color: #821517;">PARSE PASS ' . $this->snipLapCount .
                '</b></legend><p>The following snippets (if any) were parsed during this pass.</p>';
        }

        foreach ($matches[1] as $i => $call) {
            $s = &$matches[0][$i];
            if (str_starts_with($call, '$_')) {
                if (!Str::contains($content, '_PHX_INTERNAL_')) {
                    $value = $this->_getSGVar($call);
                } else {
                    $value = $s;
                }
                if (Str::contains($content, $s)) {
                    $content = str_replace($s, $value, $content);
                } elseif ($this->debug) {
                    $this->addLog('evalSnippetsSGVar parse error', $_SERVER['REQUEST_URI'] . $s, 2);
                }
                continue;
            }
            $value = $this->_get_snip_result($call);

            if (Str::contains($content, $s)) {
                if (is_null($value)) {
                    $value = '';
                }
                $content = str_replace($s, $value, $content);
            } elseif ($this->debug) {
                $this->addLog('evalSnippets parse error', $_SERVER['REQUEST_URI'] . $s, 2);
            }
        }

        if ($this->dumpSnippets) {
            $this->snippetsCode .= '</fieldset><br />';
        }

        return $content;
    }

    /**
     * @param $value
     *
     * @return mixed|string
     */
    public function _getSGVar($value): mixed
    {
        // Get super globals
        $key = $value;
        $_ = $this->getConfig('enable_filter');
        $this->setConfig('enable_filter', 1);
        [$key, $modifiers] = $this->splitKeyAndFilter($key);
        $this->setConfig('enable_filter', $_);
        $key = str_replace(['(', ')'], ["['", "']"], $key);
        $key = rtrim($key, ';');
        if (Str::contains($key, '$_SESSION')) {
            $_ = $_SESSION;
            $key = str_replace('$_SESSION', '$_', $key);
            if (isset($_['mgrFormValues'])) {
                unset($_['mgrFormValues']);
            }
            if (isset($_['token'])) {
                unset($_['token']);
            }
        }
        if (Str::contains($key, '[')) {
            $value = $key ? eval("return $key;") : '';
        } elseif (0 < eval("return count($key);")) {
            $value = eval("return print_r($key,true);");
        } else {
            $value = '';
        }
        if ($modifiers !== false) {
            $value = $this->applyFilter($value, $modifiers, $key);
        }

        return $value;
    }

    /**
     * @param $piece
     *
     * @return string|array|null
     */
    private function _get_snip_result($piece): string|array|null
    {
        if (ltrim($piece) !== $piece) {
            return '';
        }

        $eventtime = $this->dumpSnippets ? $this->getMicroTime() : 0;

        $snip_call = $this->_split_snip_call($piece);
        $key = $snip_call['name'];

        [$key, $modifiers] = $this->splitKeyAndFilter($key);
        $snip_call['name'] = $key;
        $snippetObject = $this->getSnippetObject($key);
        if ($snippetObject['content'] === null) {
            return null;
        }

        $this->currentSnippet = $snippetObject['name'];

        // current params
        $params = $this->getParamsFromString($snip_call['params']);

        if (!isset($snippetObject['properties'])) {
            $snippetObject['properties'] = [];
        }
        $default_params = $this->parseProperties($snippetObject['properties'], $this->currentSnippet, 'snippet');
        $params = array_merge($default_params, $params);

        $value = $this->evalSnippet($snippetObject['content'], $params);
        $this->currentSnippet = '';
        if ($modifiers !== false) {
            $value = $this->applyFilter($value, $modifiers, $key);
        }

        if ($this->dumpSnippets) {
            $eventtime = $this->getMicroTime() - $eventtime;
            $eventtime = sprintf('%2.2f ms', $eventtime * 1000);
            $code = str_replace("\t", '  ', $this->getPhpCompat()->htmlspecialchars($value));
            $piece = str_replace("\t", '  ', $this->getPhpCompat()->htmlspecialchars($piece));
            $print_r_params = str_replace(
                "\t",
                '  ',
                $this->getPhpCompat()->htmlspecialchars('$modx->event->params = ' . print_r($params, true))
            );
            $this->snippetsCode .= '<fieldset style="margin:1em;"><legend><b>' . $snippetObject['name'] . '</b>(' .
                $eventtime . ')</legend><pre style="white-space: pre-wrap;background-color:#fff;width:90%%;">[[' .
                $piece . ']]</pre><pre style="white-space: pre-wrap;background-color:#fff;width:90%%;">' .
                $print_r_params . '</pre><pre style="white-space: pre-wrap;background-color:#fff;width:90%%;">' .
                $code . '</pre></fieldset>';

            $this->snippetsTime[] = ['sname' => $key, 'time' => $eventtime];
        }

        return $value;
    }

    /**
     * @param $snip_name
     *
     * @return array
     */
    public function getSnippetObject($snip_name): array
    {
        if (array_key_exists($snip_name, $this->snippetCache)) {
            $snippetObject['name'] = $snip_name;
            $snippetObject['content'] = $this->snippetCache[$snip_name];
            if (isset($this->snippetCache[$snip_name . 'Props'])) {
                if (!isset($this->snippetCache[$snip_name . 'Props'])) {
                    $this->snippetCache[$snip_name . 'Props'] = '';
                }
                $snippetObject['properties'] = $this->snippetCache["{$snip_name}Props"];
            }
        } elseif (str_starts_with($snip_name, '@') && isset($this->pluginEvent[substr($snip_name, 1)])) {
            $snippetObject['name'] = substr($snip_name, 1);
            $snippetObject['content'] =
                '$rs=$this->invokeEvent("' . $snippetObject['name'] . '",$params);echo trim(implode("",$rs));';

            $snippetObject['properties'] = '';
        } else {
            $snippetObject = $this->getSnippetFromDatabase($snip_name);

            $this->snippetCache[$snip_name] = $snippetObject['content'];
            $this->snippetCache["{$snip_name}Props"] = $snippetObject['properties'];
        }

        return $snippetObject;
    }

    /**
     * @param $snip_name
     *
     * @return array
     */
    public function getSnippetFromDatabase($snip_name): array
    {
        $snippetObject = [];

        /** @var Collection $snippetModelCollection */
        $snippetModelCollection = SiteSnippet::query()
            ->where('name', '=', $snip_name)
            ->where('disabled', '=', 0)
            ->get();

        if ($snippetModelCollection->count() > 1) {
            exit('Error $modx->getSnippetObject()' . $snip_name);
        }

        if ($snippetModelCollection->count() === 1) {
            /** @var SiteSnippet $snippetModel */
            $snippetModel = $snippetModelCollection->first();
            $snip_content = $snippetModel->snippet;
            $snip_prop = $snippetModel->properties;

            $snip_prop = array_merge(
                $this->parseProperties($snip_prop),
                $this->parseProperties(optional($snippetModel->activeModule)->properties ?? [])
            );
            $snip_prop = empty($snip_prop) ? '{}' : json_encode($snip_prop);
        } else {
            $snip_content = null;
            $snip_prop = '';
        }
        $snippetObject['name'] = $snip_name;
        $snippetObject['content'] = $snip_content;
        $snippetObject['properties'] = $snip_prop;

        return $snippetObject;
    }

    /**
     * @param string $phpcode
     * @param array $params
     *
     * @return array|string
     */
    public function evalSnippet(string $phpcode, array $params): array|string
    {
        $modx = &$this;
        /*
        if(isset($params) && is_array($params)) {
        foreach($params as $k=>$v) {
        $v = strtolower($v);
        if($v==='false')    $params[$k] = false;
        elseif($v==='true') $params[$k] = true;
        }
        }*/
        if (!is_object($modx->event)) {
            $modx->event = new stdClass();
        }
        $modx->event->params = &$params; // store params inside event object
        if (is_array($params)) {
            extract($params, EXTR_SKIP);
        }
        ob_start();
        if (Str::contains($phpcode, ';')) {
            if (str_starts_with($phpcode, '<?php')) {
                $phpcode = substr($phpcode, 5);
            }
            $return = eval($phpcode);
        } elseif (!empty($phpcode)) {
            $return = call_user_func_array($phpcode, [$params]);
        } else {
            $return = '';
        }
        $echo = ob_get_clean();
        $error_info = error_get_last();
        if ((0 < $this->getConfig('error_reporting')) && $error_info !== null &&
            $this->detectError($error_info['type'])
        ) {
            $echo = ($echo === false) ? 'ob_get_contents() error' : $echo;
            $this->getService('ExceptionHandler')->messageQuit(
                'PHP Parse Error',
                '',
                true,
                $error_info['type'],
                $error_info['file'],
                'Snippet',
                $error_info['message'],
                $error_info['line'],
                $echo
            );
            if ($this->isBackend()) {
                $this->event->alert(
                    'An error occurred while loading. Please see the event log for more information' .
                    '<p>' . $echo . $return . '</p>'
                );
            }
        }
        unset($modx->event->params);
        if (is_array($return) || is_object($return)) {
            return $return;
        }

        return $echo . $return;
    }

    /**
     * @param $snippetName
     * @param array $params
     * @param int|null $cacheTime
     * @param string|null $cacheKey
     *
     * @return array|mixed|string
     */
    public function runSnippet($snippetName, array $params = [], int $cacheTime = null, string $cacheKey = null): mixed
    {
        $arrPlaceholderCheck = [];

        if (is_numeric($cacheTime) && $this->getConfig('enable_cache')) {
            $arrPlaceholderCheck = $this->placeholders;
            if (!is_string($cacheKey)) {
                $getParams = $_GET;
                ksort($getParams);
                ksort($params);
                $cacheKey = md5(json_encode($getParams) . $snippetName . json_encode($params));
            }
            $return = Cache::get($cacheKey);
            if (!is_null($return)) {
                $arrPlaceholderFromSnippet = Cache::get($cacheKey . '_placeholders');
                $this->toPlaceholders($arrPlaceholderFromSnippet);

                return $return;
            }
        }
        if (array_key_exists($snippetName, $this->snippetCache)) {
            $snippet = $this->snippetCache[$snippetName];
            $properties =
                !empty($this->snippetCache[$snippetName . "Props"]) ? $this->snippetCache[$snippetName . "Props"] : '';
        } else { // not in cache so let's check the db
            $snippetObject = $this->getSnippetFromDatabase($snippetName);
            if ($snippetObject['content'] === null) {
                $snippet = $this->snippetCache[$snippetName] = "return false;";
            } else {
                $snippet = $this->snippetCache[$snippetName] = $snippetObject['content'];
            }
            $properties = $this->snippetCache[$snippetName . "Props"] = $snippetObject['properties'];
        }
        // load default params/properties
        $parameters = $this->parseProperties($properties, $snippetName, 'snippet');
        $parameters = array_merge($parameters, $params);

        // run snippet
        $result = $this->evalSnippet($snippet, $parameters);
        if (is_numeric($cacheTime) && $this->getConfig('enable_cache')) {
            if ($cacheTime != 0) {
                Cache::put($cacheKey, $result, $cacheTime);
            } else {
                Cache::forever($cacheKey, $result);
            }

            if (!empty($this->placeholders)) {
                $arrPlaceholderCheckAfterSnippet = $this->placeholders;
                $arrPlaceholderFromSnippet =
                    array_udiff($arrPlaceholderCheckAfterSnippet, $arrPlaceholderCheck, function ($a, $b) {
                        return strcmp(json_encode($a), json_encode($b));
                    });

                if ($cacheTime != 0) {
                    Cache::put($cacheKey . '_placeholders', $arrPlaceholderFromSnippet, $cacheTime);
                } else {
                    Cache::forever($cacheKey . '_placeholders', $arrPlaceholderFromSnippet);
                }
            }
        }

        return $result;
    }

    /**
     * @param $subject
     * @param string $prefix
     *
     * @return void
     */
    public function toPlaceholders($subject, string $prefix = ''): void
    {
        if (is_object($subject)) {
            $subject = get_object_vars($subject);
        }
        if (is_array($subject)) {
            foreach ($subject as $key => $value) {
                $this->toPlaceholder($key, $value, $prefix);
            }
        }
    }

    /**
     * @param $key
     * @param $value
     * @param string $prefix
     *
     * @return void
     */
    public function toPlaceholder($key, $value, string $prefix = ''): void
    {
        if (is_array($value) || is_object($value)) {
            $this->toPlaceholders($value, $prefix . $key . '.');
        } else {
            $this->setPlaceholder($prefix . $key, $value);
        }
    }

    /**
     * @param $name
     * @param $value
     *
     * @return void
     */
    public function setPlaceholder($name, $value): void
    {
        $this->placeholders[$name] = $value;
    }

    /**
     * @param string $content
     * @param array|null $ph
     *
     * @return string
     */
    public function mergePlaceholderContent(string $content, array $ph = null): string
    {
        if ($this->getConfig('enable_at_syntax')) {
            if (stripos($content, '<@LITERAL>') !== false) {
                $content = $this->escapeLiteralTagsContent($content);
            }
        }

        if (!Str::contains($content, '[+')) {
            return $content;
        }

        if (empty($ph)) {
            $ph = $this->placeholders;
        }

        if ($this->getConfig('enable_at_syntax')) {
            $content = $this->mergeConditionalTagsContent($content);
        }

        $content = $this->mergeDocumentContent($content);
        $content = $this->mergeSettingsContent($content);
        $matches = $this->getTagsFromContent($content);

        if (empty($matches)) {
            return $content;
        }

        foreach ($matches[1] as $i => $key) {
            [$key, $modifiers] = $this->splitKeyAndFilter($key);

            if (isset($ph[$key])) {
                $value = $ph[$key];
            } elseif ($key === 'phx') {
                $value = '';
            } else {
                continue;
            }

            if ($modifiers !== false) {
                $modifiers = $this->mergePlaceholderContent($modifiers);
                $value = $this->applyFilter($value, $modifiers, $key);
            }
            $s = &$matches[0][$i];
            if (Str::contains($content, $s)) {
                $content = str_replace($s, $value, $content);
            } elseif ($this->debug) {
                $this->addLog('mergePlaceholderContent parse error', $_SERVER['REQUEST_URI'] . $s, 2);
            }
        }

        return $content;
    }

    /**
     * @param $chunkName
     *
     * @return mixed|string|null
     */
    public function getChunk($chunkName): mixed
    {
        $out = null;
        if (empty($chunkName)) {
            // nop
        } elseif ($this->isChunkProcessor('DLTemplate')) {
            $out = app('evo.tpl')->getChunk($chunkName);
        } elseif (isset($this->chunkCache[$chunkName])) {
            $out = $this->chunkCache[$chunkName];
        } elseif (stripos($chunkName, '@FILE') === 0) {
            $out = $this->chunkCache[$chunkName] = $this->atBindFileContent($chunkName);
        } else {
            $out = app('evo.tpl')->getBaseChunk($chunkName);
        }

        return $out;
    }

    /**
     * @param string $chunkName
     * @param array $chunkArr
     * @param string $prefix
     * @param string $suffix
     *
     * @return array|false|mixed|string|string[]
     */
    public function parseChunk(
        string $chunkName,
        array $chunkArr = [],
        string $prefix = '{',
        string $suffix = '}'): mixed
    {
        return $prefix === '[+' && $suffix === '+]' && $this->isChunkProcessor('DLTemplate')
            ?
            app('evo.tpl')->parseChunk($chunkName, $chunkArr)
            :
            $this->parseText($this->getChunk($chunkName), $chunkArr, $prefix, $suffix);
    }

    /**
     * @param string $str
     *
     * @return string
     */
    public function atBindFileContent(string $str = ''): string
    {
        if (stripos($str, '@FILE') !== 0) {
            return $str;
        }
        if (str_contains($str, "\n")) {
            $str = substr($str, 0, strpos("\n", $str));
        }

        if ($this->getExtFromFilename($str) === '.php') {
            return 'Could not retrieve PHP file.';
        }

        $str = substr($str, 6);
        $str = trim($str);
        if (str_contains($str, '\\')) {
            $str = str_replace('\\', '/', $str);
        }
        $str = ltrim($str, '/');

        $errorMsg = "Could not retrieve string '" . $str . "'.";

        $search_path =
            ['assets/tvs/', 'assets/chunks/', 'assets/templates/', $this->getConfig('rb_base_url') . 'files/', ''];
        foreach ($search_path as $path) {
            $file_path = MODX_BASE_PATH . $path . $str;
            if (str_starts_with($file_path, MODX_MANAGER_PATH)) {
                return $errorMsg;
            }

            if (is_file($file_path)) {
                break;
            }

            $file_path = false;
        }

        if (!$file_path) {
            return $errorMsg;
        }

        $content = file_get_contents($file_path);

        if ($content === false) {
            return $errorMsg;
        }

        return $content;
    }

    /**
     * @param $str
     *
     * @return false|string
     */
    public function getExtFromFilename($str): bool|string
    {
        $str = strtolower(trim($str));
        $pos = strrpos($str, '.');
        if ($pos === false) {
            return false;
        }

        return substr($str, $pos);
    }

    /**
     * @param string $tpl
     * @param array $ph
     * @param string $left
     * @param string $right
     * @param bool $execModifier
     *
     * @return string
     */
    public function parseText(
        string $tpl = '',
        array $ph = [],
        string $left = '[+',
        string $right = '+]',
        bool $execModifier = true): string
    {
        if (empty($ph) || empty($tpl)) {
            return $tpl;
        }

        if ($this->getConfig('enable_at_syntax')) {
            if (stripos($tpl, '<@LITERAL>') !== false) {
                $tpl = $this->escapeLiteralTagsContent($tpl);
            }
        }

        $matches = $this->getTagsFromContent($tpl, $left, $right);
        if (empty($matches)) {
            return $tpl;
        }
        foreach ($matches[1] as $i => $key) {
            if (str_contains($key, ':') && $execModifier) {
                [$key, $modifiers] = $this->splitKeyAndFilter($key);
            } else {
                $modifiers = false;
            }

            //          if(!isset($ph[$key])) continue;
            if (!array_key_exists($key, $ph)) {
                continue;
            } //NULL values must be saved in placeholders, if we got them from database string

            $value = $ph[$key];

            $s = &$matches[0][$i];
            if ($modifiers !== false) {
                if (str_contains($modifiers, $left)) {
                    $modifiers = $this->parseText($modifiers, $ph, $left, $right);
                }
                $value = $this->applyFilter($value, $modifiers, $key);
            }
            if (str_contains($tpl, $s)) {
                $tpl = str_replace($s, $value, $tpl);
            } elseif ($this->debug) {
                $this->addLog('parseText parse error', $_SERVER['REQUEST_URI'] . $s, 2);
            }
        }

        return $tpl;
    }

    /**
     * @param bool $noEvent
     * @param bool $postParse
     *
     * @return string
     */
    public function outputContent(bool $noEvent = false, bool $postParse = true): string
    {
        $this->documentOutput = $this->documentContent;

        if ($this->documentGenerated == 1
            && $this->documentObject['cacheable'] == 1
            && $this->documentObject['type'] === 'document'
            && $this->documentObject['published'] == 1
        ) {
            if ($this->sjscripts) {
                $this->documentObject['__MODxSJScripts__'] = $this->sjscripts;
            }
            if ($this->jscripts) {
                $this->documentObject['__MODxJScripts__'] = $this->jscripts;
            }
        }

        // check for non-cached snippet output
        if ($postParse && Str::contains($this->documentOutput, '[!')) {
            $this->recentUpdate = $_SERVER['REQUEST_TIME'] + $this->getConfig('server_offset_time', 0);

            $this->documentOutput = str_replace('[!', '[[', $this->documentOutput);
            $this->documentOutput = str_replace('!]', ']]', $this->documentOutput);
            $this->minParserPasses = 2;
            // Parse document source
            $this->documentOutput = $this->parseDocumentSource($this->documentOutput);
        }

        // Moved from prepareResponse() by sirlancelot
        // Insert Startup jscripts & CSS scripts into template - template must have a <head> tag
        if ($js = $this->getRegisteredClientStartupScripts()) {
            // change to just before closing </head>
            // $this->documentContent = preg_replace("/(<head[^>]*>)/i", "\\1\n".$js, $this->documentContent);
            $this->documentOutput = preg_replace("/(<\/head>)/i", $js . "\n\\1", $this->documentOutput);
        }

        // Insert jscripts & html block into template - template must have a </body> tag
        if ($js = $this->getRegisteredClientScripts()) {
            $this->documentOutput = preg_replace("/(<\/body>)/i", $js . "\n\\1", $this->documentOutput);
        }
        // End fix by sirlancelot
        if ($postParse) {
            $this->documentOutput = $this->cleanUpMODXTags($this->documentOutput);

            $this->documentOutput = $this->rewriteUrls($this->documentOutput);
        }

        // send out content-type and content-disposition headers
        if (IN_PARSER_MODE == "true") {
            $type = !empty($this->documentObject['contentType']) ? $this->documentObject['contentType'] : "text/html";
            header('Content-Type: ' . $type . '; charset=' . $this->getConfig('modx_charset'));
            // if (($this->documentIdentifier == $this->config['error_page']) || $redirect_error)
            //   header('HTTP/1.0 404 Not Found');
            if (!$this->checkPreview() && $this->documentObject['content_dispo'] == 1) {
                if ($this->documentObject['alias']) {
                    $name = $this->documentObject['alias'];
                } else {
                    // strip title of special characters
                    $name = $this->documentObject['pagetitle'];
                    $name = strip_tags($name);
                    $name = $this->cleanUpMODXTags($name);
                    $name = strtolower($name);
                    $name = preg_replace('/&.+?;/', '', $name); // kill entities
                    $name = preg_replace('/[^\.%a-z0-9 _-]/', '', $name);
                    $name = preg_replace('/\s+/', '-', $name);
                    $name = preg_replace('|-+|', '-', $name);
                    $name = trim($name, '-');
                }
                $header = 'Content-Disposition: attachment; filename=' . $name;
                header($header);
            }
        }
        $this->setConditional();

        $stats = $this->getTimerStats($this->tstart);

        if ($postParse && Str::contains($this->documentOutput, '[^')) {
            $this->documentOutput = str_replace(
                ['[^q^]', '[^qt^]', '[^p^]', '[^t^]', '[^s^]', '[^m^]'],
                [
                    $stats['queries'],
                    $stats['queryTime'],
                    $stats['phpTime'],
                    $stats['totalTime'],
                    $stats['source'],
                    $stats['phpMemory'],
                ],
                $this->documentOutput
            );
        }

        // invoke OnWebPagePrerender event
        if (!$noEvent) {
            $this->invokeEvent('OnWebPagePrerender', ['documentOutput' => &$this->documentOutput]);
        }

        $this->documentOutput = removeSanitizeSeed($this->documentOutput);

        if ($postParse) {
            if (Str::contains($this->documentOutput, '\{')) {
                $this->documentOutput = $this->RecoveryEscapedTags($this->documentOutput);
            } elseif (Str::contains($this->documentOutput, '\[')) {
                $this->documentOutput = $this->RecoveryEscapedTags($this->documentOutput);
            }
        }

        $out = $this->documentOutput;

        if ($this->dumpSQL) {
            $out .= $this->queryCode;
        }

        if ($this->dumpSnippets) {
            $sc = '';
            $tt = 0;
            foreach ($this->snippetsTime as $s => $v) {
                $t = $v['time'];
                $sname = $v['sname'];
                $sc .= sprintf('%s. %s (%2.2f ms)<br>', $s, $sname, $t); // currentSnippet
                $tt += $t;
            }
            $out .= sprintf(
                '<fieldset><legend><b>Snippets</b> (%s / %2.2f ms)</legend>%s</fieldset><br />',
                count($this->snippetsTime),
                $tt,
                $sc
            );
            $out .= $this->snippetsCode;
        }

        if ($this->dumpPlugins) {
            $ps = '';
            $tt = 0;
            foreach ($this->pluginsTime as $s => $t) {
                $ps .= sprintf('%s (%2.2f ms)<br>', $s, $t * 1000);
                $tt += $t;
            }
            $out .= sprintf(
                '<fieldset><legend><b>Plugins</b> (%s / %2.2f ms)</legend>%s</fieldset><br />',
                count($this->pluginsTime),
                $tt * 1000,
                $ps
            );
            $out .= $this->pluginsCode;
        }

        return $out;
    }

    /**
     * @return string
     */
    public function getRegisteredClientStartupScripts(): string
    {
        return implode("\n", $this->sjscripts);
    }

    /**
     * @return string
     */
    public function getRegisteredClientScripts(): string
    {
        return implode("\n", $this->jscripts);
    }

    /**
     * @param string $content
     *
     * @return array|mixed|string|string[]
     */
    public function cleanUpMODXTags(string $content = ''): mixed
    {
        if ($this->minParserPasses < 1) {
            return $content;
        }

        $enable_filter = $this->getConfig('enable_filter');
        $this->setConfig('enable_filter', 1);
        $_ = ['[* *]', '[( )]', '{{ }}', '[[ ]]', '[+ +]'];
        foreach ($_ as $brackets) {
            [$left,] = explode(' ', $brackets);
            if (str_contains($content, $left)) {
                if ($left === '[*') {
                    $content = $this->mergeDocumentContent($content);
                } elseif ($left === '[(') {
                    $content = $this->mergeSettingsContent($content);
                } elseif ($left === '{{') {
                    $content = $this->mergeChunkContent($content);
                } elseif ($left === '[[') {
                    $content = $this->evalSnippets($content);
                }
            }
        }
        foreach ($_ as $brackets) {
            [$left, $right] = explode(' ', $brackets);
            if (str_contains($content, $left)) {
                $matches = $this->getTagsFromContent($content, $left, $right);
                $content = isset($matches[0]) ? str_replace($matches[0], '', $content) : $content;
            }
        }
        $this->setConfig('enable_filter', $enable_filter);

        return $content;
    }

    /**
     * @return bool
     */
    public function checkPreview(): bool
    {
        return ($this->isLoggedIn() == true) && isset($_REQUEST['z']) && $_REQUEST['z'] === 'manprev';
    }

    /**
     * @return void
     */
    public function setConditional(): void
    {
        if (!empty($_POST) || (defined('MODX_API_MODE') && MODX_API_MODE) || $this->getLoginUserID('mgr') ||
            !$this->useConditional || empty($this->recentUpdate)
        ) {
            return;
        }
        $last_modified = gmdate('D, d M Y H:i:s T', $this->recentUpdate);
        $etag = md5($last_modified);
        $HTTP_IF_MODIFIED_SINCE = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? false;
        $HTTP_IF_NONE_MATCH = $_SERVER['HTTP_IF_NONE_MATCH'] ?? false;
        header('Pragma: no-cache');

        if ($HTTP_IF_MODIFIED_SINCE == $last_modified || Str::contains($HTTP_IF_NONE_MATCH, $etag)) {
            header('HTTP/1.1 304 Not Modified');
            header('Content-Length: 0');
            exit;
        }

        header('Last-Modified: ' . $last_modified);
        header("ETag: '" . $etag . "'");
    }

    /**
     * @param $tstart
     *
     * @return array
     */
    public function getTimerStats($tstart): array
    {
        $stats = [];

        $stats['totalTime'] = ($this->getMicroTime() - $tstart);
        $stats['queryTime'] = $this->queryTime;
        $stats['phpTime'] = $stats['totalTime'] - $stats['queryTime'];

        $stats['queryTime'] = sprintf('%2.4f s', $stats['queryTime']);
        $stats['totalTime'] = sprintf('%2.4f s', $stats['totalTime']);
        $stats['phpTime'] = sprintf('%2.4f s', $stats['phpTime']);
        $stats['source'] = $this->documentGenerated == 1 ? 'database' : 'cache';
        $stats['queries'] = $this->executedQueries ?? 0;
        $stats['phpMemory'] = (memory_get_peak_usage(true) / 1024 / 1024) . ' mb';

        return $stats;
    }

    /**
     * @param string $contents
     *
     * @return string
     */
    public function RecoveryEscapedTags(string $contents): string
    {
        [$sTags, $rTags] = $this->getTagsForEscape();

        return str_replace($rTags, $sTags, $contents);
    }

    /**
     * @return void
     */
    public function sendStrictURI(): void
    {
        $url = app('evo.url')->strictURI($this->q, $this->documentIdentifier);

        if ($url !== null) {
            $this->sendRedirect($url, 0, 'REDIRECT_HEADER', 'HTTP/1.0 301 Moved Permanently');
        }
    }

    /**
     * @return void
     */
    public function postProcess(): void
    {
        // if the current document was generated, cache it!
        $cacheable = ($this->getConfig('enable_cache') && $this->documentObject['cacheable']) ? 1 : 0;
        if ($cacheable && $this->documentGenerated && $this->documentObject['type'] == 'document' &&
            $this->documentObject['published']
        ) {
            // invoke OnBeforeSaveWebPageCache event
            $this->invokeEvent("OnBeforeSaveWebPageCache");

            if (!empty($this->cacheKey)) {
                // get and store document groups inside document object. Document groups will be used to check security on cache pages
                $docGroups =
                    DocumentGroup::query()->where('document', $this->documentIdentifier)->pluck('document_group')
                        ->toArray();
                // Attach Document Groups and Scripts
                if (is_array($docGroups)) {
                    $this->documentObject['__MODxDocGroups__'] = implode(",", $docGroups);
                }

                $docObjSerial = serialize($this->documentObject);
                $cacheContent = $docObjSerial . "<!--__MODxCacheSpliter__-->" . $this->documentContent;
                $page_cache_path = $this->getHashFile($this->cacheKey);
                Cache::forever($page_cache_path, "<?php die('Unauthorized access.'); ?>$cacheContent");
            }
        }

        // Useful for example to external page counters/stats packages
        $this->invokeEvent('OnWebPageComplete');
        // end post-processing
    }
}
