<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property int $service
 * @property string $groupname
 *
 * BelongsToMany
 * @property Collection $plugins
 */
class SystemEventname extends Model
{
    /**
     * @var string
     */
    protected $table = 'system_eventnames';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string[]
     */
    protected $casts = [
        'service' => 'int',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'service',
        'groupname',
    ];

    public function plugins(): BelongsToMany
    {
        return $this->belongsToMany(
            SitePlugin::class,
            (new SitePluginEvent())->getTable(),
            'evtid',
            'pluginid'
        )->withPivot('priority');
    }
}
