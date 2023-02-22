<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteTmplvarContentvalue extends Model
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
        'contentid' => 'int',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'tmplvarid',
        'contentid',
        'value',
    ];

    /**
     * @return BelongsTo
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(SiteContent::class, 'contentid', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function tmplvar(): BelongsTo
    {
        return $this->belongsTo(SiteTmplvar::class, 'tmplvarid', 'id');
    }
}
