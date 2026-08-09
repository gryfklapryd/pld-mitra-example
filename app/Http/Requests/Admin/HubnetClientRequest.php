<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Daftarkan/ubah klien OAuth yang boleh memakai SSO Hubnet TIRUAN.
 *
 * `client_id` dan `client_secret` TIDAK berasal dari sini: keduanya dibangkitkan
 * server saat klien dibuat (dan secret dibangkitkan ulang lewat aksi terpisah).
 * Yang diisi operator hanya nama, daftar redirect_uri, dan status aktif.
 */
final class HubnetClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Textarea satu-URL-per-baris → array. Baris kosong dibuang supaya enter
        // berlebih tidak menjadi redirect_uri kosong yang lolos.
        $raw = (string) $this->input('redirect_uris', '');
        $uris = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw) ?: [])));

        $this->merge([
            'redirect_uris' => $uris,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:190'],
            'redirect_uris' => ['required', 'array', 'min:1'],
            'redirect_uris.*' => ['required', 'string', 'max:500', 'url'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'redirect_uris.required' => 'Isi minimal satu redirect_uri.',
            'redirect_uris.*.url' => 'Setiap redirect_uri harus URL yang sah (mis. https://app/hubnet/sso).',
        ];
    }

    /**
     * @return array{name: string, redirect_uris: array<int, string>, is_active: bool}
     */
    public function payload(): array
    {
        /** @var array{name: string, redirect_uris: array<int, string>, is_active: bool} $data */
        $data = [
            'name' => $this->validated('name'),
            'redirect_uris' => array_values($this->validated('redirect_uris')),
            'is_active' => (bool) $this->validated('is_active'),
        ];

        return $data;
    }
}
