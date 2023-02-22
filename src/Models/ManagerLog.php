<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Models;

use Team64j\LaravelEvolution\Traits\TimeMutatorTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $action
 * @property int $itemid
 * @property string $username
 * @property string $message
 * @property string $itemname
 */
class ManagerLog extends Model
{
    use TimeMutatorTrait;

    /**
     * @var string
     */
    protected $table = 'manager_log';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string[]
     */
    protected $casts = [
        'timestamp' => 'datetime',
        'internalKey' => 'int',
        'action' => 'int',
        'itemid' => 'int',
        'itemname' => 'string',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'timestamp',
        'internalKey',
        'username',
        'action',
        'itemid',
        'itemname',
        'message',
        'ip',
        'useragent',
    ];
}
