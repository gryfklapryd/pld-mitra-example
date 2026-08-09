<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hubnet;

use App\Actions\ResolveAccessTokenAction;
use App\DTOs\HubnetUserInfoData;
use App\Http\Controllers\Controller;
use App\Models\HubnetUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * `GET /sso/api/user` — dipanggil pld-user dengan `Authorization: Bearer <token>`.
 *
 * Membalas `{"data_user": {...}}` — bentuk yang dibaca pld-user
 * (HubnetUserInfoResDTO). Serialisasi payload-nya milik HubnetUserInfoData; di
 * sini hanya token → identitas → bungkus.
 */
final class UserInfoController extends Controller
{
    public function __construct(
        private readonly ResolveAccessTokenAction $resolveToken,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = ($this->resolveToken)($this->bearerToken($request));

        if (! $user instanceof HubnetUser) {
            return new JsonResponse(
                ['error' => 'invalid_token', 'error_description' => 'Access token tidak sah atau kedaluwarsa.'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        return new JsonResponse([
            'data_user' => (new HubnetUserInfoData($user))->toArray(),
        ]);
    }

    /**
     * pld-user mengirim header sebagai "<token_type> <access_token>" (mis.
     * "Bearer abc"). $request->bearerToken() sudah menangani prefiks "Bearer ",
     * tetapi dibaca eksplisit di sini agar tak bergantung pada kapitalisasi.
     */
    private function bearerToken(Request $request): string
    {
        $header = (string) $request->header('Authorization', '');

        if (preg_match('/^\s*Bearer\s+(.+)$/i', $header, $m) === 1) {
            return trim($m[1]);
        }

        return trim($header);
    }
}
