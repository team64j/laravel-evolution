<?php

declare(strict_types=1);

namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $membergroup
 * @property int $documentgroup
 */
class MembergroupAccess extends Model
{
    /**
     * @var string
     */
    protected $table = 'membergroup_access';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string[]
     */
    protected $casts = [
        'membergroup' => 'int',
        'documentgroup' => 'int',
        'context' => 'int',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'membergroup',
        'documentgroup',
        'context',
    ];
}
