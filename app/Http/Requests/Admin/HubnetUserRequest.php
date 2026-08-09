<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\HubnetUserType;
use App\Models\HubnetUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Buat/ubah identitas Hubnet TIRUAN dari panel operator.
 *
 * Satu kelas untuk kedua arah: pada UBAH, `password` boleh dikosongkan (artinya
 * "biarkan seperti semula") dan keunikan (tipe+username) mengabaikan baris ini
 * sendiri. Keunikan diperiksa PER TIPE — sama dengan constraint DB — karena NIP
 * bisa saja secara kebetulan sama dengan sebuah NIB pada tipe berbeda.
 */
final class HubnetUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Checkbox yang tak dicentang tidak terkirim; jadikan boolean tegas supaya
        // "hilangkan centang aktif" benar-benar menyimpan status nonaktif.
        $this->merge([
            'status' => $this->boolean('status'),
            'is_deleted' => $this->boolean('is_deleted'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $current = $this->route('hubnet_user');
        $ignoreId = $current instanceof HubnetUser ? $current->id : null;
        $type = (int) $this->input('type');

        return [
            'type' => ['required', Rule::in(array_map(static fn (HubnetUserType $t): int => $t->value, HubnetUserType::cases()))],
            'username' => [
                'required', 'string', 'max:190',
                Rule::unique('hubnet_users', 'username')->where('type', $type)->ignore($ignoreId),
            ],
            'password' => [$ignoreId === null ? 'required' : 'nullable', 'string', 'min:6', 'max:190'],
            'name' => ['required', 'string', 'max:190'],
            'email' => ['nullable', 'email', 'max:190'],

            'nip' => ['nullable', 'string', 'max:32'],
            'nik' => ['nullable', 'string', 'max:32'],
            'npwp' => ['nullable', 'string', 'max:32'],
            'phone' => ['nullable', 'string', 'max:32'],
            'unit' => ['nullable', 'string', 'max:190'],
            'kode_unit' => ['nullable', 'string', 'max:32'],
            'golongan' => ['nullable', 'string', 'max:32'],
            'pangkat' => ['nullable', 'string', 'max:64'],
            'jabatan_fungsional' => ['nullable', 'string', 'max:190'],
            'jabatan_struktural' => ['nullable', 'string', 'max:190'],

            'status' => ['boolean'],
            'is_deleted' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.unique' => 'Sudah ada identitas dengan tipe & username itu.',
            'type.in' => 'Tipe pengguna tidak dikenal.',
        ];
    }

    /**
     * Data siap simpan. Membuang `password` kosong saat UBAH supaya tak menimpa
     * kata sandi lama dengan string kosong.
     *
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
