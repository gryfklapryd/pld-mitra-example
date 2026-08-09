<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hubnet;

use App\Actions\ExchangeAuthorizationCodeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hubnet\TokenRequest;
use App\Support\OAuthClient;
use App\Support\OAuthClientRegistry;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * `POST /sso/oauth/token` — dipanggil pld-user untuk menukar code jadi token.
 *
 * Jawaban suksesnya membawa `token_type` + `access_token`, bentuk yang dibaca
 * pld-user (HubnetVerifyAccessResDTO). Field tambahan (`expires_in`, `scope`)
 * disertakan demi kesetiaan pada OAuth2; pld-user mengabaikan field tak dikenal.
 */
final class TokenController extends Controller
{
    public function __construct(
        private readonly OAuthClientRegistry $clients,
        private readonly ExchangeAuthorizationCodeAction $exchange,
    ) {}

    public function __invoke(TokenRequest $request): JsonResponse
    {
        /** @var array{grant_type: string, code: string, client_id: string, client_secret: string, redirect_uri: string} $data */
        $data = $request->validated();

        $client = $this->clients->resolve($data['client_id']);

        if (! $client instanceof OAuthClient || ! $client->secretMatches($data['client_secret'])) {
            return $this->oauthError('invalid_client', 'Kredensial aplikasi tidak sah.', Response::HTTP_UNAUTHORIZED);
        }

        $result = ($this->exchange)($data['code'], $client, $data['redirect_uri']);

        if (! $result->ok) {
            return $this->oauthError($result->error ?? 'invalid_grant', 'Authorization code tidak sah atau sudah dipakai.', Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'token_type' => 'Bearer',
            'access_token' => $result->accessToken,
            'expires_in' => $result->expiresIn,
            'scope' => '',
        ]);
    }

    private function oauthError(string $error, string $description, int $status): JsonResponse
    {
        return new JsonResponse(['error' => $error, 'error_description' => $description], $status);
    }
}
