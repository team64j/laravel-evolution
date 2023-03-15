<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Models;

use Team64j\LaravelEvolution\Traits\TimeMutatorTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $type
 * @property int $user
 * @property int $eventid
 * @property string $source
 * @property string $createdon
 * @property string $description
 * @property User $users
 */
class EventLog extends Model
{
    use TimeMutatorTrait;

    /**
     * @var string
     */
    protected $table = 'event_log';

    public const CREATED_AT = 'createdon';

    public const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $dateFormat = 'U';

    /**
     * @var string[]
     */
    protected $casts = [
        'eventid' => 'int',
        'type' => 'int',
        'user' => 'int',
        'usertype' => 'int',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'eventid',
        'type',
        'user',
        'usertype',
        'source',
        'description',
    ];

    /**
     * @return BelongsTo
     */
    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user', 'id');
    }
}
