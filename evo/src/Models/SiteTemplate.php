<?php

declare(strict_types=1);

namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Team64j\LaravelEvolution\Traits\LockedTrait;
use Team64j\LaravelEvolution\Traits\TimeMutatorTrait;

/**
 * @property int $id
 * @property int $category
 * @property int $locked
 * @property string $templatename
 * @property string $templatealias
 * @property SiteTmplvar[]|Collection $tvs
 * @property Category $categories
 */
class SiteTemplate extends Model
{
    use LockedTrait;
    use TimeMutatorTrait;

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
        'template_type' => 'int',
        'locked' => 'int',
        'selectable' => 'int',
        'createdon' => 'int',
        'editedon' => 'int',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'templatename',
        'templatealias',
        'description',
        'editor_type',
        'category',
        'icon',
        'template_type',
        'content',
        'locked',
        'selectable',
    ];

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->setRawAttributes([
            'icon' => '',
            'category' => 0,
            'selectable' => 1,
        ]);

        parent::__construct($attributes);
    }

    /**
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category', 'id');
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function getCreatedonAttribute($value): string
    {
        return  $this->convertDateTime($value);
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function getEditedonAttribute($value): string
    {
        return  $this->convertDateTime($value);
    }

    /**
     * @return BelongsToMany
     */
    public function tvs(): BelongsToMany
    {
        return $this->belongsToMany(
            SiteTmplvar::class,
            (new SiteTmplvarTemplate())->getTable(),
            'templateid',
            'tmplvarid'
        )
            ->withPivot('rank')
            ->orderBy('pivot_rank');
    }
}
