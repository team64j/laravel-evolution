<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Managers;

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Team64j\LaravelEvolution\Facades\Uri;
use Team64j\LaravelEvolution\Models\SiteContent;
use Team64j\LaravelEvolution\Models\SiteHtmlSnippet;
use Team64j\LaravelEvolution\Models\SystemSetting;

class CoreManager
{
    /**
     * @var Application
     */
    protected Application $app;

    /**
     * @var array|null
     */
    protected ?array $currentPath = null;

    /**
     * @var int|null
     */
    protected ?int $documentIdentifier = null;

    /**
     * @var array
     */
    public array $documentObject = [];

    /**
     * @var array
     */
    protected array $resourceObject = [];

    /**
     * @var array
     */
    protected array $placeholders = [];

    /**
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->app = $application;
        $this->loadConfig();
    }

    /**
     * @return Response
     */
    public function run(): Response
    {
        if ($this->checkSiteStatus()) {
            $this->currentPath = Uri::getCurrentRoute();

            if (!$this->currentPath) {
                throw new NotFoundHttpException();
            }

            $this->documentIdentifier = $this->currentPath['id'];
            $cacheKey = $this->getCacheKey($this->documentIdentifier);
        } else {
            $this->documentIdentifier = (int) Config::get('global.site_unavailable_page');
            $cacheKey = $this->documentIdentifier;
        }

        if ($this->documentIdentifier) {
            if (Config::get('global.enable_cache')) {
                $this->resourceObject = $this->getResourceObjectFromCache($this->documentIdentifier);
            } else {
                $this->resourceObject = $this->getResourceObject($this->documentIdentifier);
            }

            $this->documentObject = array_merge(
                $this->resourceObject['document'],
                $this->resourceObject['tvs']
            );
        }

        return Cache::rememberForever('cms.document.' . $cacheKey, function () {
            if (isset($this->documentObject['content'])) {
                $this->documentObject['content'] = $this->parseCmsTags((string) $this->documentObject['content']);
            }

            $data = [
                'modx' => $this,
            ];

            $template = $this->resourceObject['tpl']['templatealias'] ?? null;

            if (isset($template) && View::exists($template)) {
                $content = View::make($template, $data)->render();
            } else {
                $content = $this->resourceObject['tpl']['content'] ?? null;

                if (Config::get('global.site_unavailable_page') == $this->documentIdentifier && is_null($content)
                    || (Config::get('global.site_unavailable_page') != $this->documentIdentifier && !$content)
                ) {
                    $content = Config::get('global.site_unavailable_message');
                }

                $template = $this->parseCmsTags((string) $content);
                $content = Blade::render($template, $data, true);
            }

            $status = match ($this->documentIdentifier) {
                (int) Config::get('global.error_page') => 404,
                (int) Config::get('global.unauthorized_page') => 403,
                (int) Config::get('global.site_unavailable_page') => 500,
                default => 200,
            };

            $headers = [
                'Content-Type' => $this->documentObject['contentType'] ?? 'text/html',
            ];

            return ResponseFacade::make($content, $status, $headers);
        });
    }

    /**
     * @return bool
     */
    protected function checkSiteStatus(): bool
    {
        return (Auth::user() && Auth::user()
                ->attributes
                ->rolePermissions
                ->where('permission', 'home')->isNotEmpty()) || Config::get('global.site_status');
    }

    /**
     * @param int $id
     *
     * @return string
     */
    protected function getCacheKey(int $id): string
    {
        $hash = $id;

        /** @var Request $request */
        $request = $this->app['request'];

        if ($request->query->keys()) {
            $query = $request->query->all();
            ksort($query);

            $hash = $id . '.' . md5(print_r($query, true));
        }

        $request->request->add(['requestKey', $hash]);

        return (string) $hash;
    }

    /**
     * @param int $id
     *
     * @return array
     */
    protected function getResourceObjectFromCache(int $id): array
    {
        return Cache::rememberForever('cms.resourceObject.' . $id, fn() => $this->getResourceObject($id));
    }

    /**
     * @param $id
     *
     * @return array
     */
    public function getResourceObject($id): array
    {
        $data = [
            'document' => [],
            'groups' => [],
            'tpl' => [],
            'tvs' => [],
        ];

        /** @var SiteContent $result */
        $result = SiteContent::query()
            ->where('id', $id)
            ->first();

        if ($result) {
            $data['document'] = $result->toArray();
            $data['groups'] = $result->documentGroups()->get()->toArray();
            $data['tpl'] = $result->tpl->withoutRelations()->toArray();
            $data['tvs'] = $result->getTvs()->keyBy('name')->toArray();
        }

        return $data;
    }

    /**
     * @param string $cmd
     *
     * @return string
     */
    private function parseCmsTags(string $cmd = ''): string
    {
        if ($cmd == '') {
            return '';
        }

        $cmd = trim($cmd);
        $reverse = str_starts_with($cmd, '!');

        if ($reverse) {
            $cmd = ltrim($cmd, '!');
        }

        if (Str::contains($cmd, '[!')) {
            $cmd = str_replace(['[!', '!]'], ['[[', ']]'], $cmd);
        }

        $safe = 0;
        while ($safe < 20) {
            $bt = md5($cmd);

            if (Str::contains($cmd, '[*')) {
                $cmd = $this->mergeDocumentContent($cmd);
            }

            if (Str::contains($cmd, '[(')) {
                $cmd = $this->mergeSettingsContent($cmd);
            }

            if (Str::contains($cmd, '{{')) {
                $cmd = $this->mergeChunkContent($cmd);
            }

            if (Str::contains($cmd, '[[')) {
                $cmd = $this->evalSnippets($cmd);
            }

            if (Str::contains($cmd, '[+') && !Str::contains($cmd, '[[')) {
                $cmd = $this->mergePlaceholderContent($cmd);
            }

            if ($bt === md5($cmd)) {
                break;
            }

            $safe++;
        }

//        $cmd = ltrim($cmd);
//        $cmd = rtrim($cmd, '-');
//        $cmd = str_ireplace([' and ', ' or '], ['&&', '||'], $cmd);

//        if (!preg_match('@^\d*$@', $cmd) && preg_match('@^[0-9<= \-\+\*/\(\)%!&|]*$@', $cmd)) {
//            $cmd = eval("return $cmd;");
//        } else {
//            $_ = explode(',', '[*,[(,{{,[[,[!,[+');
//            foreach ($_ as $left) {
//                if (Str::contains($cmd, $left)) {
//                    $cmd = '';
//                    break;
//                }
//            }
//        }

        return trim($cmd);
    }

    /**
     * @param string $content
     *
     * @return string
     */
    protected function mergeDocumentContent(string $content): string
    {
        $prefix = '[*';
        $suffix = '*]';

        $matches = $this->getTagsFromContent($content, $prefix, $suffix);

        foreach ($matches[1] as $key) {
            $value = $this->documentObject['document'][$key] ?? null;

            if (is_null($value)) {
                $value = $this->documentObject['tvs'][$key]['value'] ?? null;
            }

            if (is_null($value)) {
                $value = '';
            }

            $content = str_replace($prefix . $key . $suffix, (string) $value, $content);
        }

        return $content;
    }

    /**
     * @param string $content
     *
     * @return string
     */
    protected function mergeSettingsContent(string $content): string
    {
        $prefix = '[(';
        $suffix = ')]';

        $matches = $this->getTagsFromContent($content, $prefix, $suffix);

        foreach ($matches[1] as $key) {
            $value = Config::get('global.' . $key, '');
            $content = str_replace($prefix . $key . $suffix, (string) $value, $content);
        }

        return $content;
    }

    /**
     * @param string $content
     *
     * @return string
     */
    protected function mergeChunkContent(string $content): string
    {
        $prefix = '{{';
        $suffix = '}}';

        $matches = $this->getTagsFromContent($content, $prefix, $suffix);
        $chunks = $this->getChunks($matches[1]);

        foreach ($matches[1] as $key) {
            $value = $chunks[$key];
            $content = str_replace($prefix . $key . $suffix, (string) $value, $content);
        }

        return $content;
    }

    /**
     * @param string $content
     *
     * @return string
     */
    protected function evalSnippets(string $content): string
    {
        $prefix = '[[';
        $suffix = ']]';

        $matches = $this->getTagsFromContent($content, $prefix, $suffix);

        foreach ($matches[1] as $key) {
            //
        }

        return $content;
    }

    /**
     * @param string $content
     *
     * @return string
     */
    protected function mergePlaceholderContent(string $content): string
    {
        $prefix = '[+';
        $suffix = '+]';

        $matches = $this->getTagsFromContent($content, $prefix, $suffix);

        foreach ($matches[1] as $key) {
            $value = $this->placeholders[$key] ?? '';

            if (is_array($value)) {
                $value = json_encode($value);
            }

            $content = str_replace($prefix . $key . $suffix, (string) $value, $content);
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
    protected function getTagsFromContent(string $content, string $left = '[+', string $right = '+]'): array
    {
        $tags = [[], []];
        $_ = $this->_getTagsFromContent($content, $left, $right);

        if (empty($_)) {
            return $tags;
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
    protected function _getTagsFromContent(string $content, string $left = '[+', string $right = '+]'): array
    {
        if (!Str::contains($content, $left)) {
            return [];
        }

        $spacer = md5('<<<CMS>>>');

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
                    if (Config::get('global.enable_filter') == 1 or class_exists('PHxParser')) {
                        if (Str::contains($fetch, $left)) {
                            $nested = $this->_getTagsFromContent($fetch, $left, $right);
                            foreach ($nested as $tag) {
                                if (!in_array($tag, $tags)) {
                                    $tags[] = $tag;
                                }
                            }
                        }
                    }

                    if (!in_array($fetch, $tags)) {  // Avoid double Matches
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
     * @param array $data
     *
     * @return array
     */
    protected function getChunks(array $data): array
    {
        $chunks = [];
        $cacheKey = 'cms.chunks.';

        foreach ($data as $i => $chunk) {
            if (Cache::has('cms.chunks.' . $chunk)) {
                $chunks[$chunk] = Cache::get($cacheKey . $chunk);
                unset($data[$i]);
            }
        }

        $result = SiteHtmlSnippet::query()
            ->whereIn('name', $data)
            ->get();

        /** @var SiteHtmlSnippet $item */
        foreach ($result as $item) {
            $chunk = $item->name;

            if ($item->disabled) {
                $chunks[$chunk] = '';
            } else {
                $chunks[$chunk] = $item->snippet;
            }

            Cache::forever($cacheKey . $chunk, $chunks[$chunk]);
        }

        foreach ($data as $chunk) {
            $chunks[$chunk] = '';
        }

        return $chunks;
    }

    /**
     * @param string $method
     * @param int $id
     *
     * @return array
     */
    public function getDocumentObject(string $method, int $id): array
    {
        $resource = $this->getResourceObjectFromCache($id);

        return array_merge($resource['document'], $resource['tvs']);
    }

    /**
     * @param string $name
     * @param array $params
     *
     * @return string
     */
    public function runSnippet(string $name, array $params = []): string
    {
        return '';
    }

    public function getConfig(string $key, $default = null)
    {
        return Config::get('global.' . $key, $default);
    }

    /**
     * @return void
     */
    protected function loadConfig(): void
    {
        $dbPrefix = \env('DB_PREFIX');

        if (!is_null($dbPrefix)) {
            Config::set('database.connections.mysql.prefix', $dbPrefix);
            Config::set('database.connections.pgsql.prefix', $dbPrefix);
        }

        Config::set(
            'global',
            (array) Cache::store('file')
                ->rememberForever(
                    'cms.settings',
                    fn() => SystemSetting::query()
                        ->pluck('setting_value', 'setting_name')
                        ->toArray()
                )
        );
    }
}
