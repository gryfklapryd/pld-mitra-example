<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class PublishNotificationRequest extends FormRequest
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
            // Alamat ini dipakai PLD untuk MENCARI member, bukan sebagai alamat
            // tujuan. Email tujuan selalu diambil PLD dari datanya sendiri —
            // kalau tidak, PLD menjadi relay email terbuka atas nama AviasiHub.
            'email' => ['required', 'email', 'max:190'],

            'event_type' => ['required', 'string', 'max:64'],
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],

            'action_label' => ['nullable', 'string', 'max:40'],

            // PATH RELATIF, bukan URL. Aturan regex-nya menyalin persis apa yang
            // ditolak PLD: harus diawali "/", tidak boleh "//", tanpa backslash,
            // tanpa CR/LF/TAB. Ditangkap di sini, operator melihat sebabnya;
            // dibiarkan lolos, ia kembali sebagai 400 tanpa penjelasan.
            'action_path' => [
                'nullable',
                'string',
                'max:500',
                'regex:#^/(?!/)[^\\\\\r\n\t]*$#',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'action_path.regex' => 'actionPath harus PATH RELATIF (mis. /permohonan/123) — bukan URL lengkap, tidak diawali //, tanpa backslash.',
            'title.max' => 'Judul melebihi 150 karakter — batas kontrak PLD §4.2.',
            'message.max' => 'Isi melebihi 2000 karakter — batas kontrak PLD §4.2.',
        ];
    }
}
