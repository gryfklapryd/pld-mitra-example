<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Tiga populasi pengguna Hubnet, mengikuti field `type` pada payload
 * `/sso/api/user` dan tiga radio pada halaman login (PEGAWAI/OSS/LAINNYA).
 *
 * Nilai int-nya BUKAN sekadar urutan — angka itulah yang dikirim ke pld-user,
 * yang bercabang atasnya: 1 dan 2 dikenali, selain itu ErrForbidden.
 */
enum HubnetUserType: int
{
    case Pegawai = 1;
    case Oss = 2;
    case Lainnya = 3;

    /**
     * Label radio pada halaman login, sepersis mungkin dengan halaman asli.
     */
    public function radioLabel(): string
    {
        return match ($this) {
            self::Pegawai => 'PEGAWAI KEMENHUB',
            self::Oss => 'OSS',
            self::Lainnya => 'LAINNYA',
        };
    }

    /**
     * Petunjuk singkat identitas yang diketik di kolom USERNAME untuk tipe ini.
     */
    public function usernameHint(): string
    {
        return match ($this) {
            self::Pegawai => 'NIP',
            self::Oss => 'NIB atau email',
            self::Lainnya => 'email',
        };
    }
}
