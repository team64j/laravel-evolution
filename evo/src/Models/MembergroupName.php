<?php

declare(strict_types=1);

namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property Collection $users
 * @property Collection $documentGroups
 */
class MembergroupName extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
    ];

    /**
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'member_groups', 'user_group', 'member');
    }

    /**
     * @return BelongsToMany
     */
    public function documentGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            DocumentgroupName::class,
            'membergroup_access',
            'membergroup',
            'documentgroup'
        )
            ->withPivot(['id', 'context']);
    }
}
