<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Models;

use Illuminate\Database\Eloquent\Model;

class SitePluginEvent extends Model
{
    /**
     * @var string
     */
    protected $table = 'site_plugin_events';

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string[]
     */
    protected $casts = [
        'pluginid' => 'int',
        'evtid' => 'int',
        'priority' => 'int',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'pluginid',
        'evtid',
        'priority',
    ];
}
