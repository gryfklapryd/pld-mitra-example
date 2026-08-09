<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Authorization code OAuth sekali pakai. Yang tersimpan hash-nya.
 *
 * @property int $id
 * @property int $hubnet_user_id
 * @property string $code_hash
 * @property string $client_id
 * @property string $redirect_uri
 * @property Carbon $expires_at
 * @property Carbon|null $used_at
 */
class HubnetAuthorizationCode extends Model
{
    protected $fillable = [
        'hubnet_user_id',
        'code_hash',
        'client_id',
        'redirect_uri',
        'expires_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<HubnetUser, $this> */
    public function hubnetUser(): BelongsTo
    {
        return $this->belongsTo(HubnetUser::class);
    }

    /**
     * Code yang masih boleh ditukar: belum dipakai DAN belum kedaluwarsa.
     *
     * @param  Builder<HubnetAuthorizationCode>  $query
     * @return Builder<HubnetAuthorizationCode>
     */
    public function scopeRedeemable(Builder $query): Builder
    {
        return $query->whereNull('used_at')->where('expires_at', '>', now());
    }
}
