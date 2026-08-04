<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\ValidateMemberCredentialsAction;
use App\Actions\ValidationOutcome;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UserValidationRequest;
use App\Services\Contracts\IntegrationLoggerContract;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * `API User Validation URL` — dipanggil PLD saat member menautkan akunnya.
 *
 * Pemetaan jawaban mengikuti kontrak v1.0.1 persis, dan bedanya bermakna di sisi
 * PLD (lihat pld-integration `internal_service.go` UserValidation):
 *
 *   200 {"is_valid": true}  → tautan dibuat
 *   200 {"is_valid": false} → password salah; PLD MENGHITUNGNYA sebagai percobaan gagal
 *   400                     → user_login tak dikenal; juga dihitung
 *   5xx / timeout           → dianggap gangguan aplikasi kita, TIDAK dihitung
 *
 * Baris terakhir itu sebabnya kegagalan internal tidak boleh dibalas 200 dengan
 * is_valid=false: member akan terkunci karena masalah yang bukan salahnya.
 */
final class UserValidationController extends Controller
{
    public function __construct(
        private readonly ValidateMemberCredentialsAction $validateCredentials,
        private readonly IntegrationLoggerContract $logger,
    ) {}

    public function __invoke(UserValidationRequest $request): JsonResponse
    {
        $startedAt = microtime(true);

        /** @var array{user_login: string, password: string} $validated */
        $validated = $request->validated();

        $outcome = ($this->validateCredentials)(
            $validated['user_login'],
            $validated['password'],
        );

        [$payload, $status] = match ($outcome) {
            ValidationOutcome::Valid => [['is_valid' => true], Response::HTTP_OK],
            ValidationOutcome::Invalid => [['is_valid' => false], Response::HTTP_OK],
            ValidationOutcome::NotFound => [
                ['message' => 'User tidak ditemukan.'],
                Response::HTTP_BAD_REQUEST,
            ],
        };

        // request()->all() memuat password — IntegrationLogger menyamarkannya
        // sebelum menyentuh basis data. Lihat App\Support\PayloadRedactor.
        $this->logger->inbound(
            endpoint: 'API User Validation URL',
            request: $request->all(),
            response: $payload,
            status: $status,
            remoteIp: $request->ip(),
            durationMs: (int) round((microtime(true) - $startedAt) * 1000),
        );

        return new JsonResponse($payload, $status);
    }
}
