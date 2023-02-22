<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

/**
 * @property int $id
 * @property int $category
 * @property int $locked
 * @property string $default_text
 * @property Category $categories
 */
class SiteTmplvar extends Model
{
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
     * @return BelongsTo
     */
    public function categories(): BelongsTo
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
}
