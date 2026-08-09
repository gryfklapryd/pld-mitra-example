<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Access token Hubnet TIRUAN. Yang tersimpan hash-nya.
 *
 * @property int $id
 * @property int $hubnet_user_id
 * @property string $token_hash
 * @property Carbon $expires_at
 */
class HubnetAccessToken extends Model
{
    protected $fillable = [
        'hubnet_user_id',
        'token_hash',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<HubnetUser, $this> */
    public function hubnetUser(): BelongsTo
    {
        return $this->belongsTo(HubnetUser::class);
    }

    /**
     * @param  Builder<HubnetAccessToken>  $query
     * @return Builder<HubnetAccessToken>
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }
}
