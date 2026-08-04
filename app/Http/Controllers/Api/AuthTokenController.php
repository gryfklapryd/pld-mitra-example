<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\IssueSsoTokenAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AuthTokenRequest;
use App\Repositories\Contracts\MemberRepositoryContract;
use App\Services\Contracts\IntegrationLoggerContract;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * `API Auth URL` — PLD menukar `user_login` dengan token auto-login.
 *
 * Alur lengkapnya: member klik layanan di portal PLD → PLD memanggil endpoint ini
 * → kita balas token → PLD meredirect member ke `Redirect URL` + token → kita
 * tukar token itu jadi sesi (lihat SsoLandingController).
 */
final class AuthTokenController extends Controller
{
    public function __construct(
        private readonly MemberRepositoryContract $members,
        private readonly IssueSsoTokenAction $issueToken,
        private readonly IntegrationLoggerContract $logger,
    ) {}

    public function __invoke(AuthTokenRequest $request): JsonResponse
    {
        $startedAt = microtime(true);

        /** @var array{user_login: string} $validated */
        $validated = $request->validated();
        $member = $this->members->findByUserLogin($validated['user_login']);

        if ($member === null || ! $member->is_active) {
            // Satu jawaban untuk "tidak ada" dan "nonaktif". Membedakannya
            // menjadikan endpoint ini alat memeriksa keberadaan username, padahal
            // pemanggilnya hanya perlu tahu bahwa SSO tidak bisa dilanjutkan.
            return $this->respond(
                $request,
                ['message' => 'User tidak ditemukan.'],
                Response::HTTP_BAD_REQUEST,
                $startedAt,
            );
        }

        $issued = ($this->issueToken)($member);

        return $this->respond(
            $request,
            [
                'auth_token' => $issued['token'],
                'expires_in' => $issued['expiresIn'],
            ],
            Response::HTTP_OK,
            $startedAt,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function respond(
        AuthTokenRequest $request,
        array $payload,
        int $status,
        float $startedAt,
    ): JsonResponse {
        $this->logger->inbound(
            endpoint: 'API Auth URL',
            request: $request->all(),
            response: $payload,
            status: $status,
            remoteIp: $request->ip(),
            durationMs: (int) round((microtime(true) - $startedAt) * 1000),
        );

        return new JsonResponse($payload, $status);
    }
}
