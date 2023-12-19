<?php

namespace Team64j\LaravelEvolution\Traits;

use Illuminate\Support\Facades\Config;

trait Settings
{
    /**
     * @var array
     */
    public array $config = [];

    /**
     * Needs only for the configCompatibility hack
     *
     * @var array
     */
    protected array $saveConfig = [];

    /**
     * @var array|string[]
     */
    protected array $casts = [
        'error_reporting' => 'int',
        'site_start' => 'int',
        'error_page' => 'int',
        'cache_type' => 'int',
        'unauthorized_page' => 'int',
        'server_offset_time' => 'int',
        'site_unavailable_page' => 'int',
        'site_status' => 'bool',
        'use_alias_path' => 'bool',
        'seostrict' => 'bool',
        'make_folders' => 'bool',
        'friendly_urls' => 'bool',
        'xhtml_urls' => 'bool',
        'alias_listing' => 'int',
        'friendly_alias_urls' => 'bool',
        'enable_cache' => 'float',
        'enable_at_syntax' => 'bool',
        'enable_filter' => 'bool',
        'use_udperms' => 'bool',
        'manager_menu_height' => 'float',
        'manager_tree_width' => 'float',
        'show_picker' => 'bool',
        'show_fullscreen_btn' => 'bool',
        'export_includenoncache' => 'bool',
        'number_of_results' => 'int',
        'manager_theme_mode' => 'int',
        'manager_login_startup' => 'int',
        'global_tabs' => 'bool',
        'remember_last_tab' => 'bool',
        'show_newresource_btn' => 'bool',
        'use_browser' => 'bool',
        'warning_visibility' => 'bool',
        'use_captcha' => 'bool',
        'tree_page_click' => 'int',
        'allow_multiple_emails' => 'bool',
        'use_editor' => 'bool',
        'group_tvs' => 'int',
        'jpegQuality' => 'int',
        'thumbHeight' => 'int',
        'maxImageHeight' => 'int',
        'maxImageWidth' => 'int',
        'denyExtensionRename' => 'bool',
        'denyZipDownload' => 'bool',
        'showHiddenFiles' => 'bool',
        'new_folder_permissions' => 'string', //Don't set int
        'new_file_permissions' => 'string', //Don't set int
        'upload_maxsize' => 'int',
        'noThumbnailsRecreation' => 'bool',
        'clientResize' => 'bool',
        'publish_default' => 'bool',
        'search_default' => 'bool',
        'cache_default' => 'bool',
        'auto_menuindex' => 'bool',
        'track_visitors' => 'bool',
    ];

    /**
     * @param $name
     * @param $value
     */
    public function setConfig($name, $value): void
    {
        $this->config[$name] = $value;
    }

    /**
     * @return array
     */
    public function allConfig(): array
    {
        return array_merge(
            $this->config,
            Config::get('global', [])
        );
    }

    /**
     * @param string $name
     * @param $default
     *
     * @return bool|float|int|mixed|string|null
     */
    public function getConfig(string $name = '', $default = null): mixed
    {
        $config = $this->config[$name] ?? '';

        if ($config === '') {
            $config = $default;
        }

        $value = Config::get('global.' . $name, $config);

        return $this->castAttribute($name, $value);
    }

    /**
     * Get Evolution CMS settings including, but not limited to, the system_settings table
     */
    public function getSettings(): void
    {
        $this->config = array_merge($this->getFactorySettings(), $this->config);

        // setup default site id - new installation should generate a unique id for the site.
        if ($this->getConfig('site_id', '') === '') {
            $this->setConfig('site_id', 'MzGeQ2faT4Dw06+U49x3');
        }

        // store base_url and base_path inside config array
        $this->setConfig('base_url', MODX_BASE_URL);
        $this->setConfig('base_path', MODX_BASE_PATH);
        $this->setConfig('site_url', MODX_SITE_URL);
        $this->setConfig('site_manager_path', MODX_MANAGER_PATH);
        $this->error_reporting = $this->getConfig('error_reporting');
        $this->setConfig(
            'filemanager_path',
            str_replace(
                '[(base_path)]',
                MODX_BASE_PATH,
                $this->getConfig('filemanager_path')
            )
        );

        $this->setConfig(
            'snapshot_path',
            str_replace(
                '[(base_path)]',
                MODX_BASE_PATH,
                $this->getConfig('snapshot_path')
            )
        );

        $this->setConfig(
            'rb_base_dir',
            str_replace(
                '[(base_path)]',
                MODX_BASE_PATH,
                $this->getConfig('rb_base_dir')
            )
        );
    }

    public function getFactorySettings(): array
    {
        $out = include EVO_CORE_PATH . 'factory/settings.php';

        return \is_array($out) ? $out : [];
    }

    /**
     * Cast an attribute to a native PHP type.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return mixed
     * @see \Illuminate\Database\Eloquent\Concerns\HasAttributes::castAttribute
     */
    protected function castAttribute($key, $value)
    {
        if ($value === null) {
            return null;
        }

        return match ($this->getCastType($key)) {
            'int', 'integer' => (int) $value,
            'real', 'float', 'double' => (float) $value,
            'string' => (string) $value,
            'bool', 'boolean' => (bool) $value,
            default => $value,
        };
    }

    protected function getCastType($key)
    {
        return isset($this->casts[$key]) ? trim(strtolower($this->casts[$key])) : null;
    }

    /**
     * Hack for compatibility Laravel with EvolutionCMS
     *
     * @TODO: This is dirty code. Any ideas?
     *
     * @return array
     */
    protected function configCompatibility(): array
    {
        return array_merge($this->config, ['view' => config('view')]);
    }
}
