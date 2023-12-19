<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Team64j\LaravelEvolution\Traits\LockedTrait;

/**
 * @property int $category
 * @property int $locked
 * @property int $disabled
 * @property string $description
 * @property Category $categories
 * @method static Builder|SitePlugin activePhx()
 */
class SitePlugin extends Model
{
    use LockedTrait;

    public const CREATED_AT = 'createdon';
    public const UPDATED_AT = 'editedon';

    /**
     * @var string
     */
    protected $table = 'site_plugins';

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
        'cache_type' => 'bool',
        'locked' => 'int',
        'disabled' => 'int',
        'createdon' => 'int',
        'editedon' => 'int',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'description',
        'editor_type',
        'category',
        'cache_type',
        'plugincode',
        'locked',
        'properties',
        'disabled',
        'moduleguid',
    ];

    /**
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category', 'id');
    }

    public function scopeActivePhx(Builder $builder)
    {
        return $builder->where('disabled', '!=', 1)
            ->where('plugincode', 'LIKE', "%phx.parser.class.inc.php%OnParseDocument();%");
    }
}
