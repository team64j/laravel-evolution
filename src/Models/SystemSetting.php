<?php

declare(strict_types=1);

namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $setting_name
 * @property string $setting_value
 */
class SystemSetting extends Model
{
    /**
     * @var string
     */
    protected $primaryKey = 'setting_name';

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
    protected $fillable = [
        'setting_name',
        'setting_value',
    ];
}
