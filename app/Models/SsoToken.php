<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Token auto-login sekali pakai. Yang tersimpan hash-nya, bukan nilainya.
 *
 * @property int $id
 * @property int $member_id
 * @property string $token_hash
 * @property Carbon $expires_at
 * @property Carbon|null $used_at
 */
class SsoToken extends Model
{
    protected $fillable = [
        'member_id',
        'token_hash',
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

    /** @return BelongsTo<Member, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Token yang masih boleh ditukar: belum dipakai DAN belum kedaluwarsa.
     *
     * @param  Builder<SsoToken>  $query
     * @return Builder<SsoToken>
     */
    public function scopeRedeemable(Builder $query): Builder
    {
        return $query->whereNull('used_at')->where('expires_at', '>', now());
    }
}
