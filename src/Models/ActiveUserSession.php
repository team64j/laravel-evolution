<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Models;

use Team64j\LaravelEvolution\Traits\TimeMutatorTrait;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActiveUserSession extends Model
{
    use TimeMutatorTrait;

    /**
     * @var string
     */
    protected $primaryKey = 'sid';

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
        'internalKey' => 'int',
        'lasthit' => 'datetime',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'sid',
        'internalKey',
        'lasthit',
        'ip',
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'internalKey', 'id');
    }
}
