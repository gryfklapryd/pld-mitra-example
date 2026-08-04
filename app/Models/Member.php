<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MemberFactory;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Pengguna aplikasi ini. `user_login` adalah identitas yang dikenal PLD.
 *
 * @property int $id
 * @property string $user_login
 * @property string $name
 * @property string|null $email
 * @property string $password
 * @property bool $is_active
 */
class Member extends Authenticatable implements AuthenticatableContract
{
    /** @use HasFactory<MemberFactory> */
    use HasFactory;

    protected $fillable = [
        'user_login',
        'name',
        'email',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<Application, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /** @return HasMany<SsoToken, $this> */
    public function ssoTokens(): HasMany
    {
        return $this->hasMany(SsoToken::class);
    }

    /**
     * Pencocokan `user_login` yang tidak peduli besar-kecil huruf.
     *
     * PLD me-lowercase userLogin saat memetakan jawaban tracking ke member, jadi
     * mencari dengan `where('user_login', $x)` yang case-sensitive akan membuat
     * kita menjawab untuk "Budi" sementara PLD mencari kunci "budi" — item
     * terkirim, tapi tak pernah sampai ke siapa pun.
     *
     * @param  Builder<Member>  $query
     * @return Builder<Member>
     */
    public function scopeByUserLogin(Builder $query, string $userLogin): Builder
    {
        return $query->whereRaw('LOWER(user_login) = ?', [mb_strtolower(trim($userLogin))]);
    }

    /**
     * @param  Builder<Member>  $query
     * @return Builder<Member>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
