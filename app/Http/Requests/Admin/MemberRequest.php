<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Member;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Buat/ubah member layanan dari panel operator.
 *
 * `user_login` unik tanpa memandang besar-kecil huruf — sama dengan kunci yang
 * dicocokkan PLD saat menautkan akun & menyinkronkan tracking. Pada UBAH,
 * `password` boleh dikosongkan (biarkan seperti semula).
 */
final class MemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $current = $this->route('member');
        $ignoreId = $current instanceof Member ? $current->id : null;

        return [
            'user_login' => [
                'required', 'string', 'max:100',
                Rule::unique('members', 'user_login')->ignore($ignoreId),
            ],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:190'],
            'password' => [$ignoreId === null ? 'required' : 'nullable', 'string', 'min:8'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_login.unique' => 'user_login itu sudah dipakai member lain.',
            'user_login.required' => 'user_login wajib diisi — inilah yang diketik member saat menautkan akun di PLD.',
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
