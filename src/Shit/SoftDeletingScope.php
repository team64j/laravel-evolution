<?php

namespace Team64j\LaravelEvolution\Shit;

use Illuminate\Database\Eloquent;

class SoftDeletingScope extends Eloquent\SoftDeletingScope
{
    protected $extensions = ['Restore', 'WithTrashed', 'WithoutTrashed', 'OnlyTrashed'];

    public function apply(Eloquent\Builder $builder, Eloquent\Model $model)
    {
        $builder->where($model->getQualifiedDeletedColumn(), '=', 0);
    }

    protected function addWithoutTrashed(Eloquent\Builder $builder)
    {
        $builder->macro('withoutTrashed', function (Eloquent\Builder $builder) {
            $model = $builder->getModel();
            $builder->withoutGlobalScope($this)->where(
                $model->getQualifiedDeletedColumn(),
                '=',
                0
            );

            return $builder;
        });
    }

    protected function addOnlyTrashed(Eloquent\Builder $builder)
    {
        $builder->macro('onlyTrashed', function (Eloquent\Builder $builder) {
            $model = $builder->getModel();
            $builder->withoutGlobalScope($this)->where(
                $model->getQualifiedDeletedColumn(),
                '!=',
                0
            );

            return $builder;
        });
    }
}
