<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

/**
 * @property int $category
 * @property int $locked
 * @property int $disabled
 * @property string $modulecode
 * @property string $description
 * @property Category $categories
 */
class SiteModule extends Model
{
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
        'disabled' => 'int',
        'category' => 'int',
        'wrap' => 'int',
        'locked' => 'int',
        'enable_resource' => 'int',
        'createdon' => 'int',
        'editedon' => 'int',
        'enable_sharedparams' => 'int',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'description',
        'editor_type',
        'disabled',
        'category',
        'wrap',
        'locked',
        'icon',
        'enable_resource',
        'resourcefile',
        'guid',
        'enable_sharedparams',
        'properties',
        'modulecode',
    ];

    /**
     * @return BelongsTo
     */
    public function categories(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category', 'id');
    }

    /**
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeWithoutProtected(Builder $builder): Builder
    {
        if (Auth::user()['attributes']->role != 1 && Config::get('global.use_udperms')) {
            $userGroups = MemberGroup::query()->where('member', Auth::user()->id)->get()->pluck('user_group');
            $moduleIds = SiteModuleAccess::query()->whereIn('usergroup', $userGroups)->get()->pluck('module');
            $builder->whereIn('id', $moduleIds);

//            $builder->leftJoin('site_module_access', 'site_module_access.module', '=', 'site_modules.id')
//                ->leftJoin('member_groups', 'member_groups.user_group', '=', 'site_module_access.usergroup')
//                ->whereNull('site_module_access.usergroup')
//                ->orWhere('member_groups.member', '=', Auth::user()->id);
        }

        return $builder;
    }
}
