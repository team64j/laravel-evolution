<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Models;

use Illuminate\Database\Eloquent\Model;

class ActiveUserLock extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string[]
     */
    protected $casts = [
        'internalKey' => 'int',
        'elementType' => 'int',
        'elementId' => 'int',
        'lasthit' => 'int'
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'sid',
        'internalKey',
        'elementType',
        'elementId',
        'lasthit'
    ];
}
