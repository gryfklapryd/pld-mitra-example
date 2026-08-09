<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hubnet;

use App\Actions\AuthenticateHubnetUserAction;
use App\Actions\IssueAuthorizationCodeAction;
use App\Enums\HubnetUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hubnet\AuthorizeRequest;
use App\Http\Requests\Hubnet\LoginRequest;
use App\Models\HubnetUser;
use App\Support\HubnetSeedAccounts;
use App\Support\OAuthClient;
use App\Support\OAuthClientRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Endpoint authorize Hubnet TIRUAN — kaki peramban dari alur SSO.
 *
 * GET  menampilkan halaman login (meniru hubnet.kemenhub.go.id/sso).
 * POST memverifikasi kredensial dummy, menerbitkan authorization code, dan
 *      meredirect kembali ke redirect_uri milik PLD dengan `code` (+ `state`).
 *
 * Keabsahan klien & redirect_uri diperiksa di KEDUA kaki: pada GET supaya orang
 * tidak melihat form untuk klien tak dikenal, dan pada POST supaya field
 * tersembunyi yang diutak-atik tidak bisa mengarahkan code ke tujuan lain.
 */
final class AuthorizeController extends Controller
{
    public function __construct(
        private readonly OAuthClientRegistry $clients,
        private readonly AuthenticateHubnetUserAction $authenticate,
        private readonly IssueAuthorizationCodeAction $issueCode,
    ) {}

    public function show(AuthorizeRequest $request): View
    {
        $client = $this->clients->resolve((string) $request->validated('client_id'));

        if (! $client instanceof OAuthClient) {
            return view('hubnet.error', [
                'message' => 'Aplikasi (client_id) tidak dikenali oleh Hubnet.',
            ]);
        }

        $redirectUri = (string) ($request->validated('redirect_uri') ?? $client->defaultRedirectUri());

        if ($redirectUri === '' || ! $client->allowsRedirectUri($redirectUri)) {
            // redirect_uri yang tak terdaftar TIDAK BOLEH dipakai untuk meredirect —
            // justru itu yang dilindungi. Galatnya ditampilkan di sini, tidak
            // dikirim balik ke tujuan yang belum tentu tepercaya.
            return view('hubnet.error', [
                'message' => 'Alamat pengalihan (redirect_uri) tidak terdaftar untuk aplikasi ini.',
            ]);
        }

        return view('hubnet.login', [
            'clientId' => $client->id,
            'redirectUri' => $redirectUri,
            'state' => (string) ($request->validated('state') ?? ''),
            'types' => HubnetUserType::cases(),
            'version' => (string) config('hubnet.display_version'),
            'accounts' => array_map(static fn (array $a): array => [
                'type' => $a['type']->value,
                'typeLabel' => $a['type']->radioLabel(),
                'username' => $a['username'],
                'name' => $a['name'],
                'note' => $a['note'],
            ], HubnetSeedAccounts::all()),
            'demoPassword' => HubnetSeedAccounts::PASSWORD,
        ]);
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $client = $this->clients->resolve((string) $request->validated('client_id'));
        $redirectUri = (string) $request->validated('redirect_uri');

        if (! $client instanceof OAuthClient || ! $client->allowsRedirectUri($redirectUri)) {
            return back()->withInput()->withErrors([
                'username' => 'Parameter aplikasi tidak sah. Mulai ulang dari tombol SSO di portal.',
            ]);
        }

        $user = ($this->authenticate)(
            $request->userType(),
            (string) $request->validated('username'),
            (string) $request->validated('password'),
        );

        if (! $user instanceof HubnetUser) {
            // Satu pesan untuk "username tak ada" dan "password salah": membedakannya
            // hanya menolong penebak.
            return back()->withInput($request->except('password'))->withErrors([
                'username' => 'Username atau password salah.',
            ]);
        }

        $code = ($this->issueCode)($user, $client, $redirectUri);

        return redirect()->away($this->appendQuery($redirectUri, [
            'code' => $code,
            'state' => (string) $request->validated('state', ''),
        ]));
    }

    /**
     * @param  array<string, string>  $params
     */
    private function appendQuery(string $uri, array $params): string
    {
        $params = array_filter($params, static fn (string $v): bool => $v !== '');
        $separator = str_contains($uri, '?') ? '&' : '?';

        return $uri.$separator.http_build_query($params);
    }
}
