<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $document_group
 * @property int $document
 */
class DocumentGroup extends Model
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string[]
     */
    protected $casts = [
        'document_group' => 'int',
        'document' => 'int',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'document_group',
        'document',
    ];
}
