<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Member;
use App\Repositories\Contracts\SsoTokenRepositoryContract;
use Illuminate\Support\Str;

/**
 * Menerbitkan token auto-login untuk `API Auth URL`.
 *
 * Yang dikembalikan adalah nilai MENTAH (dipakai PLD untuk meredirect member);
 * yang disimpan hanya hash-nya. Tabel `sso_tokens` karenanya tidak berisi apa pun
 * yang bisa dipakai masuk bila isinya bocor.
 */
final readonly class IssueSsoTokenAction
{
    public function __construct(
        private SsoTokenRepositoryContract $tokens,
    ) {}

    /**
     * @return array{token: string, expiresIn: int}
     */
    public function __invoke(Member $member): array
    {
        $ttl = (int) config('pld.auth_token_ttl');
        $token = Str::random(64);

        // Token 64 karakter acak — bukan rahasia yang ditebak manusia, jadi hash
        // cepat (SHA-256) sudah cukup. bcrypt di sini hanya menambah ~100ms pada
        // jalur yang dilewati member setiap kali klik SSO, tanpa keamanan tambahan.
        $this->tokens->issue($member, hash('sha256', $token), $ttl);

        // Membersihkan jejak lama sekalian: token yang sudah mati tak berguna bagi
        // siapa pun, dan tabel yang tumbuh selamanya adalah utang yang menagih
        // sendiri di kemudian hari.
        $this->tokens->pruneExpiredFor($member);

        return ['token' => $token, 'expiresIn' => $ttl];
    }
}
