<?php

declare(strict_types=1);

namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $module
 * @property int $resource
 * @property int $type
 */
class SiteModuleDepobj extends Model
{
    /**
     * @var string
     */
    protected $table = 'site_module_depobj';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $casts = [
        'module' => 'int',
        'resource' => 'int',
        'type' => 'int',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'module',
        'resource',
        'type',
    ];
}
