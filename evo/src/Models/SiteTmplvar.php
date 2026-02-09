<?php

declare(strict_types=1);

namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Team64j\LaravelEvolution\Traits\LockedTrait;

/**
 * @property int $id
 * @property string $name
 * @property string $caption
 * @property string $description
 * @property int $category
 * @property int $locked
 * @property string $type
 * @property string $default_text
 * @property string $display
 * @property string $display_params
 * @property Category $categories
 * @property array|UserRole[] $roles
 * @property array|SiteTemplate[] $templates
 * @property array|DocumentgroupName[] $permissions
 */
class SiteTmplvar extends Model
{
    use LockedTrait;

    public const CREATED_AT = 'createdon';
    public const UPDATED_AT = 'editedon';

    /**
     * @var string
     */
    protected $dateFormat = 'U';

    /**
     * @var string[]
     */
    protected $casts = [
        'editor_type' => 'int',
        'category' => 'int',
        'locked' => 'int',
        'rank' => 'int',
        'createdon' => 'int',
        'editedon' => 'int',
        'properties' => 'array',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'type',
        'name',
        'caption',
        'description',
        'editor_type',
        'category',
        'locked',
        'elements',
        'rank',
        'display',
        'display_params',
        'default_text',
        'properties',
    ];

    /**
     * @var array|string[]
     */
    protected array $standardTypes = [
        'text' => 'Text',
        'rawtext' => 'Raw Text (deprecated)',
        'textarea' => 'Textarea',
        'rawtextarea' => 'Raw Textarea (deprecated)',
        'textareamini' => 'Textarea (Mini)',
        'richtext' => 'RichText',
        'dropdown' => 'DropDown List Menu',
        'listbox' => 'Listbox (Single-Select)',
        'listbox-multiple' => 'Listbox (Multi-Select)',
        'option' => 'Radio Options',
        'checkbox' => 'Check Box',
        'image' => 'Image',
        'file' => 'File',
        'url' => 'URL',
        'email' => 'Email',
        'number' => 'Number',
        'date' => 'Date',
    ];

    /**
     * @var array
     */
    protected array $displayWidgets = [
        'datagrid' => 'Data Grid',
        'richtext' => 'RichText',
        'viewport' => 'View Port',
        'custom_widget' => 'Custom Widget',
    ];

    /**
     * @var array
     */
    protected array $displayFormats = [
        'htmlentities' => 'HTML Entities',
        'date' => 'Date Formatter',
        'unixtime' => 'Unixtime',
        'delim' => 'Delimited List',
        'htmltag' => 'HTML Generic Tag',
        'hyperlink' => 'Hyperlink',
        'image' => 'Image',
        'string' => 'String Formatter',
    ];

    /**
     * @return bool|null
     */
    public function delete(): ?bool
    {
        $this->tmplvarContentvalue()->delete();
        $this->tmplvarAccess()->delete();
        $this->tmplvarTemplate()->delete();

        return parent::delete();
    }

    /**
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category', 'id');
    }

    /**
     * @return HasMany
     */
    public function tmplvarUserRole(): HasMany
    {
        return $this->hasMany(UserRoleVar::class, 'tmplvarid', 'id');
    }

    /**
     * @return BelongsToMany
     */
    public function templates(): BelongsToMany
    {
        return $this->belongsToMany(
            SiteTemplate::class,
            (new SiteTmplvarTemplate())->getTable(),
            'tmplvarid',
            'templateid'
        )
            ->withPivot('rank')
            ->orderBy('pivot_rank', 'ASC');
    }

    /**
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            UserRole::class,
            (new UserRoleVar())->getTable(),
            'tmplvarid',
            'roleid'
        )
            ->withPivot('rank')
            ->orderBy('pivot_rank', 'ASC');
    }

    /**
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            DocumentgroupName::class,
            (new SiteTmplvarAccess())->getTable(),
            'tmplvarid',
            'documentgroup'
        );
    }

    /**
     * @return HasMany
     */
    public function tmplvarContentvalue(): HasMany
    {
        return $this->hasMany(SiteTmplvarContentvalue::class, 'tmplvarid', 'id');
    }

    /**
     * @return HasMany
     */
    public function tmplvarAccess(): HasMany
    {
        return $this->hasMany(SiteTmplvarAccess::class, 'tmplvarid', 'id');
    }

    /**
     * @return HasMany
     */
    public function tmplvarTemplate(): HasMany
    {
        return $this->hasMany(SiteTmplvarTemplate::class, 'tmplvarid', 'id');
    }

    /**
     * @param string $key
     *
     * @return array|null
     */
    public function parameterType(string $key): ?array
    {
        return array_map(function ($group) use ($key) {
            $group['data'] = array_filter($group['data'], fn($item) => $item['key'] == $key);

            if (isset($group['data'][0])) {
                $group['data'][0]['selected'] = true;
            }

            return $group;
        }, $this->parameterTypes());
    }

    /**
     * @return array
     */
    public function parameterTypes(): array
    {
        $data = [
            [
                'name' => 'Standard Type',
                'data' => $this->parameterStandardTypes(),
            ],
        ];

        if ($customTypes = $this->parameterCustomTypes()) {
            $data[] = [
                'name' => 'Custom Type',
                'data' => $customTypes,
            ];
        }

        return $data;
    }

    /**
     * @return array
     */
    protected function parameterStandardTypes(): array
    {
        $standardTypes = [];

        foreach ($this->standardTypes as $key => $type) {
            $standardTypes[] = [
                'key' => $key,
                'value' => $type,
            ];
        }

        return $standardTypes;
    }

    /**
     * @return array
     */
    protected function parameterCustomTypes(): array
    {
        $customTvs = [];
        $path = dirname(base_path()) . '/assets/tvs';

        if (!is_dir($path)) {
            return $customTvs;
        }

        $finder = Finder::create()
            ->in($path)
            ->depth(0)
            ->notName('/^index\.html$/')
            ->sortByName();

        /** @var SplFileInfo $ctv */
        foreach ($finder as $ctv) {
            $filename = $ctv->getFilename();
            $customTvs[] = [
                'key' => 'custom_tv:' . $filename,
                'value' => $filename,
            ];
        }

        return $customTvs;
    }

    /**
     * @return array
     */
    public function getStandardTypes(): array
    {
        return $this->standardTypes;
    }

    /**
     * @return array
     */
    public function getDisplayFormats(): array
    {
        return $this->displayFormats;
    }

    /**
     * @return array
     */
    public function getDisplayWidgets(): array
    {
        return $this->displayWidgets;
    }

    /**
     * @param string|null $key
     *
     * @return array|string
     */
    public function getDisplay(?string $key = null): array|string
    {
        if (!is_null($key)) {
            return ($this->displayWidgets + $this->displayFormats)[$key] ?? '';
        }

        return [
            [
                'name' => 'Widgets',
                'data' => array_map(fn($key, $value) => [
                    'key' => $key,
                    'value' => $value,
                ], array_keys($this->displayWidgets), $this->displayWidgets),
            ],
            [
                'name' => 'Formats',
                'data' => array_map(fn($key, $value) => [
                    'key' => $key,
                    'value' => $value,
                ], array_keys($this->displayFormats), $this->displayFormats),
            ],
        ];
    }
}
