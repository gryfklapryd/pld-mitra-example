<?php

declare(strict_types=1);

namespace App\Http\Requests\Hubnet;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi query authorize (GET /sso/oauth/authorize).
 *
 * Hanya bentuk parameter yang divalidasi di sini; keabsahan client_id/redirect_uri
 * terhadap klien terdaftar diperiksa controller lewat OAuthClientRegistry, karena
 * jawabannya bukan "422 field salah" melainkan halaman galat OAuth.
 *
 * `state` opsional dan tidak diwajibkan: alur PLD/FE hari ini tidak mengirimnya,
 * jadi mewajibkannya akan mematahkan justru alur yang ingin diuji. Bila ada, ia
 * dikembalikan apa adanya pada redirect (round-trip CSRF milik klien).
 */
final class AuthorizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'string', 'max:190'],
            'redirect_uri' => ['nullable', 'string', 'max:500', 'url'],
            'response_type' => ['nullable', 'string', 'max:40'],
            'scope' => ['nullable', 'string', 'max:190'],
            'state' => ['nullable', 'string', 'max:500'],
            'login_api' => ['nullable', 'string', 'max:500'],
        ];
    }
}
