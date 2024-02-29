<?php

declare(strict_types=1);

namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Team64j\LaravelEvolution\Traits\LockedTrait;

/**
 * @property Category $categories
 * @property int $category
 * @property int $locked
 * @property int $disabled
 * @property string $name
 * @property string $description
 */
class SiteHtmlSnippet extends Model
{
    use LockedTrait;

    /**
     * @var string
     */
    protected $table = 'site_htmlsnippets';

    const CREATED_AT = 'createdon';
    const UPDATED_AT = 'editedon';

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
        'createdon' => 'int',
        'editedon' => 'int',
        'disabled' => 'int',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'description',
        'editor_type',
        'editor_name',
        'category',
        'cache_type',
        'snippet',
        'locked',
        'disabled',
    ];

    /**
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category', 'id');
    }
}
