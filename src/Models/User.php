<?php

declare(strict_types=1);

namespace Team64j\LaravelEvolution\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $username
 * @property UserSetting[]|Collection $settings
 */
class User extends \Illuminate\Foundation\Auth\User
{
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $rememberTokenName = 'access_token';

    /**
     * @var string[]
     */
    protected $hidden = [
        'password',
        'cachepwd',
        'verified_key',
        'refresh_token',
        'access_token',
        'valid_to',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'username',
        'password',
        'cachepwd',
        'verified_key',
        'refresh_token',
        'access_token',
        'valid_to',
    ];

    /**
     * @return HasOne
     */
    public function attributes(): HasOne
    {
        return $this->hasOne(UserAttribute::class, 'internalKey', 'id');
    }

    /**
     * @return HasMany
     */
    public function memberGroups(): HasMany
    {
        return $this->hasMany(MemberGroup::class, 'member', 'id');
    }

    /**
     * @return HasMany
     */
    public function settings(): HasMany
    {
        return $this->hasMany(UserSetting::class, 'user', 'id');
    }

    /**
     * @return HasMany
     */
    public function values(): HasMany
    {
        return $this->hasMany(UserValue::class, 'userid', 'id');
    }

    /**
     * @return bool|null
     */
    public function delete(): ?bool
    {
        $this->memberGroups()->delete();
        $this->attributes()->delete();
        $this->settings()->delete();

        return parent::delete();
    }
}
