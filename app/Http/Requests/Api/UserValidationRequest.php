<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permintaan `API User Validation URL` dari PLD.
 *
 * Bentuk badan: {"user_login": "...", "password": "..."}.
 *
 * `password` di sini adalah password member DI APLIKASI INI yang ia ketikkan di
 * portal PLD saat menautkan akun. PLD meneruskannya untuk kita cocokkan.
 */
final class UserValidationRequest extends FormRequest
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
            'user_login' => ['required', 'string', 'max:100'],
            // Tanpa aturan panjang minimum: yang diperiksa di sini bentuk payload,
            // bukan kekuatan password. Menolak password pendek pada jalur validasi
            // akan membuat akun lama yang password-nya memang pendek mustahil
            // ditautkan, dan gejalanya di portal PLD hanya "kredensial salah".
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Pesan Bahasa Indonesia, sejalan dengan dua endpoint kontrak lainnya.
     *
     * Bukan sekadar kerapian: `message` inilah yang diteruskan PLD ke layar
     * pengelola layanan saat penautan gagal, dan pesan bawaan Laravel
     * ("The user login field is required.") menyebut nama field dalam bentuk yang
     * tidak ada di dokumen kontrak mana pun.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_login.required' => 'user_login wajib diisi.',
            'password.required' => 'password wajib diisi.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(new JsonResponse(
            ['message' => $validator->errors()->first()],
            Response::HTTP_BAD_REQUEST,
        ));
    }
}
