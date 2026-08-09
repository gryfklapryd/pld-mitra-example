<?php

declare(strict_types=1);

namespace App\Http\Requests\Hubnet;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penukaran code → token (POST /sso/oauth/token), dipanggil MESIN (pld-user).
 *
 * pld-user mengirim body sebagai JSON (Content-Type: application/json), bukan
 * form-urlencoded seperti diminta RFC 6749. Laravel membaca keduanya lewat
 * input() yang sama, jadi endpoint ini menerima apa yang pld-user kirim hari ini
 * TANPA memaksa perubahan di sisi pld-user — tujuan tiruan ini adalah membuat
 * alur yang ADA bisa jalan, bukan mengoreksi kontraknya.
 *
 * `grant_type` dikunci ke authorization_code: satu-satunya alur yang didukung.
 */
final class TokenRequest extends FormRequest
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
            'grant_type' => ['required', Rule::in(['authorization_code'])],
            'code' => ['required', 'string', 'max:190'],
            'client_id' => ['required', 'string', 'max:190'],
            'client_secret' => ['required', 'string', 'max:255'],
            'redirect_uri' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * Pemanggilnya mesin di path /sso/* (bukan api/*), jadi penanganan galat
     * bawaan akan MEREDIRECT alih-alih membalas JSON. Dipaksa balas 400 dengan
     * bentuk galat OAuth2 supaya pld-user membacanya sebagai kegagalan, bukan
     * mengikuti redirect ke halaman lain.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(new JsonResponse(
            ['error' => 'invalid_request', 'error_description' => $validator->errors()->first()],
            Response::HTTP_BAD_REQUEST,
        ));
    }
}
