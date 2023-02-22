<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserValue extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string[]
     */
    protected $casts = [
        'tmplvarid' => 'int',
        'userid' => 'int',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'tmplvarid',
        'userid',
        'value',
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userid', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function tmplvar(): BelongsTo
    {
        return $this->belongsTo(SiteTmplvar::class, 'tmplvarid', 'id');
    }
}
