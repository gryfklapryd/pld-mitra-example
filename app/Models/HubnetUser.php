<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\HubnetUserType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu identitas di Hubnet TIRUAN — data DUMMY, bukan pengguna aplikasi ini.
 *
 * Mewakili apa yang di dunia nyata dipegang Pusdatin: pegawai Kemenhub, badan
 * usaha OSS, pengguna eksternal. Dibaca dua kali dalam satu alur SSO: sekali
 * untuk memverifikasi kredensial di halaman login, sekali untuk membentuk payload
 * `/sso/api/user` yang diminta pld-user.
 *
 * @property int $id
 * @property HubnetUserType $type
 * @property string $username
 * @property string $password
 * @property string $name
 * @property string|null $email
 * @property string|null $nip
 * @property string|null $nik
 * @property string|null $npwp
 * @property string|null $phone
 * @property string|null $unit
 * @property string|null $kode_unit
 * @property string|null $golongan
 * @property string|null $pangkat
 * @property string|null $jabatan_fungsional
 * @property string|null $jabatan_struktural
 * @property bool $status
 * @property bool $is_deleted
 */
class HubnetUser extends Model
{
    protected $fillable = [
        'type',
        'username',
        'password',
        'name',
        'email',
        'nip',
        'nik',
        'npwp',
        'phone',
        'unit',
        'kode_unit',
        'golongan',
        'pangkat',
        'jabatan_fungsional',
        'jabatan_struktural',
        'status',
        'is_deleted',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'type' => HubnetUserType::class,
            'password' => 'hashed',
            'status' => 'boolean',
            'is_deleted' => 'boolean',
        ];
    }

    /** @return HasMany<HubnetAuthorizationCode, $this> */
    public function authorizationCodes(): HasMany
    {
        return $this->hasMany(HubnetAuthorizationCode::class);
    }

    /** @return HasMany<HubnetAccessToken, $this> */
    public function accessTokens(): HasMany
    {
        return $this->hasMany(HubnetAccessToken::class);
    }

    /**
     * Pencocokan (tipe, username) yang tidak peka besar-kecil huruf.
     *
     * NIP tak berhuruf, tapi username OSS/lainnya bisa email — dan email tak peka
     * besar-kecil di bagian domainnya. Meniadakan kepekaan huruf di sini membuat
     * "Tock@Gmail.com" dan "tock@gmail.com" tidak jadi dua identitas berbeda.
     *
     * @param  Builder<HubnetUser>  $query
     * @return Builder<HubnetUser>
     */
    public function scopeCredential(Builder $query, HubnetUserType $type, string $username): Builder
    {
        return $query
            ->where('type', $type->value)
            ->whereRaw('LOWER(username) = ?', [mb_strtolower(trim($username))]);
    }
}
