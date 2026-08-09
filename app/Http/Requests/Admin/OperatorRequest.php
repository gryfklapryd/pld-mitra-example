<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Buat/ubah operator internal (panel /admin) dari layar.
 *
 * Kata sandi minimal 12 karakter — sama seperti perintah pel:operator, dan bukan
 * angka karangan: akun ini membuka panel yang bisa mengirim email berkop resmi
 * atas nama layanan. Pada UBAH, kosongkan untuk membiarkan kata sandi lama.
 */
final class OperatorRequest extends FormRequest
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
        $current = $this->route('operator');
        $ignoreId = $current instanceof User ? $current->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:190',
                Rule::unique('users', 'email')->ignore($ignoreId),
            ],
            'password' => [$ignoreId === null ? 'required' : 'nullable', 'string', 'min:12'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Email itu sudah dipakai operator lain.',
            'password.min' => 'Kata sandi minimal 12 karakter — akun ini membuka panel yang bisa mengirim email atas nama layanan.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $data = $this->validated();

        if (($data['password'] ?? '') === '' || $data['password'] === null) {
            unset($data['password']);
        }

        return $data;
    }
}
