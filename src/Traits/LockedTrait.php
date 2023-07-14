<?php

namespace Team64j\LaravelEvolution\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * @method static Builder withoutLocked()
 */
trait LockedTrait
{
    /**
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeWithoutLocked(Builder $builder): Builder
    {
        if (!Auth::user()->isAdmin()) {
            $builder->where('locked', 0);
        }

        return $builder;
    }
}
