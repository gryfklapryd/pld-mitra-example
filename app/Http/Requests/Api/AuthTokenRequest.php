<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permintaan `API Auth URL` dari PLD.
 *
 * Bentuk badan: {"user_login": "..."} — snake_case, mengikuti kontrak v1.0.1
 * (lihat pld-integration `internal_service.go` AuthToken). Perhatikan bahwa
 * endpoint tracking memakai camelCase; perbedaan itu ada di kontraknya, bukan
 * kekeliruan penyalinan.
 */
final class AuthTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Otorisasi ditangani middleware VerifyPldApiKey — tidak ada pengguna
        // bersesi pada jalur ini.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_login' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_login.required' => 'user_login wajib diisi.',
        ];
    }

    /**
     * Kontrak menetapkan bentuk galat {"message": "..."}, bukan bentuk 422
     * bawaan Laravel yang memuat objek `errors`. PLD membaca `message` untuk
     * ditampilkan; bentuk lain hanya akan tampil sebagai galat generik.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(new JsonResponse(
            ['message' => $validator->errors()->first()],
            Response::HTTP_BAD_REQUEST,
        ));
    }
}
