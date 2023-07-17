<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Team64j\LaravelEvolution\Traits\TimeMutatorTrait;

/**
 * @property int $id
 * @property int $parent
 * @property int $alias_visible
 * @property int $isfolder
 * @property int $template
 * @property int $deleted
 * @property int $richtext
 * @property string $alias
 * @property string $pagetitle
 * @property string $type
 * @property string $contentType
 * @property string $content_dispo
 * @property SiteTemplate $tpl
 * @property SiteContent $parents
 * @property SiteTmplvarContentvalue[]|Collection $templateValues
 * @property DocumentgroupName[]|Collection $documentGroups
 */
class SiteContent extends Model
{
    use TimeMutatorTrait;

    public const CREATED_AT = 'createdon';
    public const UPDATED_AT = 'editedon';

    /**
     * @var string
     */
    protected $table = 'site_content';

    /**
     * @var string
     */
    protected $dateFormat = 'U';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->setRawAttributes([
            'published' => Config::get('global.publish_default'),
            'template' => Config::get('global.default_template'),
            'hide_from_tree' => 0,
            'alias_visible' => 1,
            'richtext' => 1,
            'menuindex' => 0,
            'searchable' => Config::get('global.search_default'),
            'cacheable' => Config::get('global.cache_default'),
            'type' => 'document',
            'contentType' => 'text/html',
            'parent' => 0,
            'content_dispo' => 0,
        ], true);

        parent::__construct($attributes);
    }

    /**
     * @var array|string[]
     */
    protected $casts = [
        'published' => 'int',
        'pub_date' => 'int',
        'unpub_date' => 'int',
        'parent' => 'int',
        'isfolder' => 'int',
        'richtext' => 'int',
        'template' => 'int',
        'menuindex' => 'int',
        'searchable' => 'int',
        'cacheable' => 'int',
        'createdby' => 'int',
        'createdon' => 'datetime',
        'editedby' => 'int',
        'editedon' => 'datetime',
        'deleted' => 'int',
        'deletedby' => 'int',
        'publishedon' => 'datetime',
        'publishedby' => 'int',
        'hide_from_tree' => 'int',
        'privateweb' => 'int',
        'privatemgr' => 'int',
        'content_dispo' => 'int',
        'hidemenu' => 'int',
        'alias_visible' => 'int',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'type',
        'contentType',
        'pagetitle',
        'longtitle',
        'description',
        'alias',
        'link_attributes',
        'published',
        'pub_date',
        'unpub_date',
        'parent',
        'isfolder',
        'introtext',
        'content',
        'richtext',
        'template',
        'menuindex',
        'searchable',
        'cacheable',
        'createdby',
        'editedby',
        'deleted',
        'deletedby',
        'publishedon',
        'publishedby',
        'menutitle',
        'hide_from_tree',
        'privateweb',
        'privatemgr',
        'content_dispo',
        'hidemenu',
        'alias_visible',
    ];

    /**
     * @param $value
     *
     * @return string
     */
    public function setDescriptionAttribute($value): string
    {
        return (string) $value;
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function setIntrotextAttribute($value): string
    {
        return (string) $value;
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function setMenutitleAttribute($value): string
    {
        return (string) $value;
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function setLongtitleAttribute($value): string
    {
        return (string) $value;
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function setLinkAttributesAttribute($value): string
    {
        return (string) $value;
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function setCreatedonAttribute($value): string
    {
        return (string) $value;
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function setEditedonAttribute($value): string
    {
        return (string) $value;
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function setPublishedonAttribute($value): string
    {
        return (string) $value;
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function getCreatedonAttribute($value): string
    {
        return $this->convertDateTime($value);
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function getEditedonAttribute($value): string
    {
        return $this->convertDateTime($value);
    }

    /**
     * @return Collection
     */
    public function getTvs(): Collection
    {
        /** @var Collection $docTv */
        if ($this->tpl->tvs === null) {
            return Collection::make();
        }

        $docTv = $this->templateValues->pluck('value', 'tmplvarid');

        return $this->tpl->tvs()->with('category')->get()->map(function (SiteTmplvar $tmplvar) use ($docTv) {
            $value = $docTv->has($tmplvar->getKey()) ? $docTv->get($tmplvar->getKey()) : $tmplvar->default_text;

            switch ($tmplvar->type) {
                case 'radio':
                case 'checkbox':
                case 'option':
                case 'listbox-multiple':
                    if (!is_array($value)) {
                        $value = $value == '' ? [] : explode('||', $value);
                    }

                    break;

                default:
            }

            $category = $tmplvar->getRelation('category');

            return array_merge(
                $tmplvar->withoutRelations()->toArray(),
                [
                    'value' => $value,
                    'category_name' => $category->category ?: Lang::get('global.no_category'),
                    'pivot_rank' => $tmplvar->pivot->rank,
                ]
            );
        });
    }

    /**
     * @return BelongsTo
     */
    public function tpl(): BelongsTo
    {
        return $this->belongsTo(SiteTemplate::class, 'template', 'id')->withDefault();
    }

    /**
     * @return HasMany
     */
    public function templateValues(): HasMany
    {
        return $this->hasMany(SiteTmplvarContentvalue::class, 'contentid', 'id');
    }

    /**
     * @return BelongsToMany
     */
    public function documentGroups(): BelongsToMany
    {
        return $this->belongsToMany(DocumentgroupName::class, 'document_groups', 'document', 'document_group');
    }

    /**
     * @return Builder|BelongsTo
     */
    public function parents(): BelongsTo | Builder
    {
        return $this->belongsTo(SiteContent::class, 'parent', 'id')->with('parents');
    }

    /**
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(SiteContent::class, 'parent', 'id')->with('children');
    }
}
