<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class StoreApplicationRequest extends FormRequest
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
            'member_id' => ['required', 'integer', 'exists:members,id'],

            // Batas panjang mengikuti kontrak §4.1, bukan angka karangan sendiri.
            // Melebihinya berarti item DIBUANG PLD tanpa pesan apa pun ke kita.
            'label' => ['required', 'string', 'max:200'],

            'stage_names' => ['required', 'array', 'min:1', 'max:'.config('pld.limits.timeline_entries')],
            'stage_names.*' => ['required', 'string', 'max:120'],

            'contact_unit' => ['nullable', 'string', 'max:200'],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'label.max' => 'Nama proses melebihi 200 karakter — batas kontrak PLD §4.1.',
            'stage_names.required' => 'Minimal satu tahap wajib diisi.',
            'stage_names.*.max' => 'Nama tahap melebihi 120 karakter — batas kontrak PLD §4.2.',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function stageNames(): array
    {
        /** @var array<int, string> $names */
        $names = $this->validated('stage_names');

        return array_values(array_filter(array_map(trim(...), $names)));
    }
}
