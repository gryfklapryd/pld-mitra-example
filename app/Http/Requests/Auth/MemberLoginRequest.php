<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Login member ke aplikasi ini sendiri.
 *
 * Pasangan kredensial di sini SAMA PERSIS dengan yang diteruskan PLD ke
 * `API User Validation URL` — memang itu maksudnya. Member mengetikkan
 * `user_login` + password PEL miliknya di portal PLD saat menautkan akun, dan
 * PLD meneruskannya ke kita untuk dicocokkan.
 *
 * Karena itu keduanya WAJIB memakai pemeriksa yang sama
 * (App\Actions\ValidateMemberCredentialsAction). Implementasi paralel adalah
 * sumber bug klasik: password diganti lewat aplikasi, penautan di PLD tetap
 * memakai jalur lama, dan tak seorang pun tahu mengapa.
 */
final class MemberLoginRequest extends FormRequest
{
    /**
     * Batas percobaan sebelum dikunci sementara.
     *
     * Dikunci per (user_login + IP), bukan per user_login saja: mengunci
     * berdasarkan user_login saja berarti siapa pun yang tahu nama akun orang
     * lain bisa mengunci akun itu dari luar hanya dengan menebak asal-asalan.
     */
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

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
            'user_login' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_login.required' => 'Nama pengguna wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ];
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'user_login' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$seconds} detik.",
        ]);
    }

    public function recordFailedAttempt(): void
    {
        RateLimiter::hit($this->throttleKey(), self::DECAY_SECONDS);
    }

    public function clearAttempts(): void
    {
        RateLimiter::clear($this->throttleKey());
    }

    private function throttleKey(): string
    {
        return 'member-login:'.Str::transliterate(
            Str::lower((string) $this->string('user_login')).'|'.$this->ip(),
        );
    }
}
