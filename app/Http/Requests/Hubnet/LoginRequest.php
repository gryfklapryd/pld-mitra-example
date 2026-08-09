<?php

declare(strict_types=1);

namespace App\Http\Requests\Hubnet;

use App\Enums\HubnetUserType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Login pada halaman Hubnet TIRUAN (POST /sso/oauth/authorize).
 *
 * Membawa kredensial (username+password+tipe radio) BERSAMA parameter OAuth yang
 * diteruskan lewat field tersembunyi (client_id, redirect_uri, state) — karena
 * setelah login berhasil, ke sanalah code harus dikirim balik. Nilai-nilai OAuth
 * itu tetap divalidasi bentuknya di sini; keabsahannya terhadap klien terdaftar
 * diperiksa ulang di controller.
 */
final class LoginRequest extends FormRequest
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
            'username' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string', 'max:190'],
            'type' => ['required', Rule::in(array_map(static fn (HubnetUserType $t): int => $t->value, HubnetUserType::cases()))],

            'client_id' => ['required', 'string', 'max:190'],
            'redirect_uri' => ['required', 'string', 'max:500', 'url'],
            'state' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'type.required' => 'Pilih salah satu jenis pengguna.',
            'type.in' => 'Jenis pengguna tidak dikenal.',
        ];
    }

    public function userType(): HubnetUserType
    {
        return HubnetUserType::from((int) $this->integer('type'));
    }
}
