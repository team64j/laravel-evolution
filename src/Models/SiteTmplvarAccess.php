<?php

declare(strict_types=1);

namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteTmplvarAccess extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;
    /**
     * @var string
     */
    protected $table = 'site_tmplvar_access';
    /**
     * @var string[]
     */
    protected $casts = [
        'tmplvarid' => 'int',
        'documentgroup' => 'int',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'tmplvarid',
        'documentgroup',
    ];

    /**
     * @return BelongsTo
     */
    public function tmplvar(): BelongsTo
    {
        return $this->belongsTo(SiteTmplvar::class, 'tmplvarid', 'id');
    }
}
