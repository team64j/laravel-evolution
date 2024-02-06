<?php

declare(strict_types=1);

namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * EvolutionCMS\Models\DocumentgroupName
 *
 * @property int $id
 * @property string $name
 * @property int $private_memgroup
 * @property int $private_webgroup
 * @property Collection $documents
 * @property Collection $memberGroups
 * @property Collection $webGroups
 */
class DocumentgroupName extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string[]
     */
    protected $casts = [
        'private_memgroup' => 'int',
        'private_webgroup' => 'int',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'private_memgroup',
        'private_webgroup',
    ];

    /**
     * @return BelongsToMany
     */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(
            SiteContent::class,
            'document_groups',
            'document_group',
            'document'
        );
    }

    /**
     * @return BelongsToMany
     */
    public function memberGroups(): BelongsToMany
    {
        return $this->belongsToMany(MembergroupName::class, 'membergroup_access', 'documentgroup', 'membergroup');
    }
}
