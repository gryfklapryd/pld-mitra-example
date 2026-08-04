<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permintaan `API Tracking URL` dari PLD — kontrak §3.
 *
 * Bentuk badan: {"contractVersion": "1.0", "userLogins": [...]} — camelCase.
 */
final class TrackingRequest extends FormRequest
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
        $max = (int) config('pld.limits.user_logins_per_request');

        return [
            // Wajib ada sejak versi pertama. TIDAK dicocokkan dengan versi kita:
            // kontrak §8 mengharuskan implementasi mengabaikan yang tak dikenal
            // alih-alih menolaknya, supaya penambahan field opsional di kemudian
            // hari tidak mematikan integrasi yang sedang berjalan.
            'contractVersion' => ['required', 'string', 'max:16'],

            'userLogins' => ['required', 'array', 'min:1', 'max:'.$max],
            'userLogins.*' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $max = (int) config('pld.limits.user_logins_per_request');

        return [
            'contractVersion.required' => 'contractVersion wajib diisi.',
            'userLogins.required' => 'userLogins wajib diisi.',
            'userLogins.max' => "userLogins melebihi batas {$max} per permintaan.",
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
