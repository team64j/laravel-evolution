<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $module
 * @property int $usergroup
 */
class SiteModuleAccess extends Model
{
    /**
     * @var string
     */
    protected $table = 'site_module_access';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $casts = [
        'module' => 'int',
        'usergroup' => 'int',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'module',
        'usergroup',
    ];
}
