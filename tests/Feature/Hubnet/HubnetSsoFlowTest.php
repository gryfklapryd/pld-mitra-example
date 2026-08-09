<?php

declare(strict_types=1);

namespace Tests\Feature\Hubnet;

use App\Enums\HubnetUserType;
use App\Models\HubnetUser;
use App\Support\HubnetSeedAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Alur SSO Hubnet TIRUAN ujung ke ujung, dari sudut pandang pld-user:
 * authorize (peramban) → token (mesin) → userinfo (mesin).
 *
 * Bentuk payload di sini adalah KONTRAK sesungguhnya dengan pld-user
 * (HubnetDataUser). Uji yang menjaga `type` int dan `status`/`is_deleted` angka
 * bukan formalitas: pld-user membaca dua field terakhir sebagai json.RawMessage
 * dan hanya mengenali "1"/"0" — string apa pun diam-diam melewati cek B5.
 */
final class HubnetSsoFlowTest extends TestCase
{
    use RefreshDatabase;

    private const CLIENT_ID = '0bc24bbf-4912-4621-aa00-361795f3e18e';

    private const SECRET = 'rahasia-klien-uji';

    private const REDIRECT = 'https://pld-dev.test/hubnet/sso';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'hubnet.client_id' => self::CLIENT_ID,
            'hubnet.client_secret' => self::SECRET,
            'hubnet.redirect_uris' => [self::REDIRECT],
            'hubnet.code_ttl' => 120,
            'hubnet.token_ttl' => 300,
        ]);
    }

    #[Test]
    public function alur_penuh_pegawai_menghasilkan_payload_type_1(): void
    {
        $this->seedAccount(HubnetUserType::Pegawai, '199608082022031008');

        $code = $this->login('199608082022031008', HubnetUserType::Pegawai);
        $token = $this->exchange($code);

        $response = $this->withToken($token)->getJson('/sso/api/user');

        $response->assertOk()
            ->assertJsonPath('data_user.type', 1)
            ->assertJsonPath('data_user.username', '199608082022031008')
            ->assertJsonPath('data_user.nip', '199608082022031008')
            ->assertJsonPath('data_user.status', 1)
            ->assertJsonPath('data_user.is_deleted', 0);
    }

    #[Test]
    public function payload_oss_type_2_tidak_membawa_nip_maupun_status(): void
    {
        $this->seedAccount(HubnetUserType::Oss, '1409210000868');

        $code = $this->login('1409210000868', HubnetUserType::Oss);
        $token = $this->exchange($code);

        $this->withToken($token)->getJson('/sso/api/user')
            ->assertOk()
            ->assertJsonPath('data_user.type', 2)
            ->assertJsonPath('data_user.nip', null)
            ->assertJsonMissingPath('data_user.status');
    }

    #[Test]
    public function status_nonaktif_terbawa_sebagai_angka_nol(): void
    {
        // B5: pld-user memblokir bila status=0. Nilainya harus 0 (angka), bukan "0".
        $this->seedAccount(HubnetUserType::Pegawai, '198701012010012002', ['status' => false]);

        $code = $this->login('198701012010012002', HubnetUserType::Pegawai);
        $token = $this->exchange($code);

        $this->withToken($token)->getJson('/sso/api/user')
            ->assertOk()
            ->assertJsonPath('data_user.status', 0);
    }

    #[Test]
    public function authorization_code_hanya_bisa_ditukar_sekali(): void
    {
        $this->seedAccount(HubnetUserType::Pegawai, '199608082022031008');
        $code = $this->login('199608082022031008', HubnetUserType::Pegawai);

        $this->tokenRequest($code)->assertOk();
        $this->tokenRequest($code)->assertStatus(400)->assertJsonPath('error', 'invalid_grant');
    }

    #[Test]
    public function client_secret_salah_ditolak_invalid_client(): void
    {
        $this->seedAccount(HubnetUserType::Pegawai, '199608082022031008');
        $code = $this->login('199608082022031008', HubnetUserType::Pegawai);

        $this->postJson('/sso/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => self::CLIENT_ID,
            'client_secret' => 'salah',
            'redirect_uri' => self::REDIRECT,
        ])->assertStatus(401)->assertJsonPath('error', 'invalid_client');
    }

    #[Test]
    public function redirect_uri_yang_berbeda_saat_menukar_ditolak(): void
    {
        // Code terikat pada redirect_uri saat diterbitkan. Menukarnya dengan tujuan
        // lain — anatomi code yang dicuri lalu dialihkan — harus gagal.
        config(['hubnet.redirect_uris' => [self::REDIRECT, 'https://lain.test/hubnet/sso']]);
        $this->seedAccount(HubnetUserType::Pegawai, '199608082022031008');
        $code = $this->login('199608082022031008', HubnetUserType::Pegawai);

        $this->postJson('/sso/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::SECRET,
            'redirect_uri' => 'https://lain.test/hubnet/sso',
        ])->assertStatus(400)->assertJsonPath('error', 'invalid_grant');
    }

    #[Test]
    public function authorize_menolak_redirect_uri_tak_terdaftar_tanpa_meredirect(): void
    {
        $this->get('/sso/oauth/authorize?'.http_build_query([
            'client_id' => self::CLIENT_ID,
            'redirect_uri' => 'https://jahat.test/curi',
            'response_type' => 'code',
        ]))->assertOk()->assertSee('tidak terdaftar', false);
    }

    #[Test]
    public function userinfo_tanpa_token_sah_dijawab_401(): void
    {
        $this->withToken('token-ngawur')->getJson('/sso/api/user')
            ->assertStatus(401)->assertJsonPath('error', 'invalid_token');
    }

    #[Test]
    public function kredensial_salah_tidak_menerbitkan_code(): void
    {
        $this->seedAccount(HubnetUserType::Pegawai, '199608082022031008');

        $this->post('/sso/oauth/authorize', [
            'client_id' => self::CLIENT_ID,
            'redirect_uri' => self::REDIRECT,
            'username' => '199608082022031008',
            'password' => 'password-salah',
            'type' => HubnetUserType::Pegawai->value,
        ])->assertSessionHasErrors('username');
    }

    // --- pembantu ---------------------------------------------------------

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function seedAccount(HubnetUserType $type, string $username, array $overrides = []): HubnetUser
    {
        return HubnetUser::query()->create(array_merge([
            'type' => $type,
            'username' => $username,
            'password' => HubnetSeedAccounts::PASSWORD,
            'name' => 'UJI',
            'email' => 'uji@example.test',
            'nip' => $type === HubnetUserType::Pegawai ? $username : null,
            'nik' => '3514140808960004',
            'npwp' => $type === HubnetUserType::Oss ? '094883561402000' : null,
            'kode_unit' => '005000000000000',
            'unit' => 'Direktorat Jenderal Perhubungan Udara',
            'status' => true,
            'is_deleted' => false,
        ], $overrides));
    }

    private function login(string $username, HubnetUserType $type): string
    {
        $response = $this->post('/sso/oauth/authorize', [
            'client_id' => self::CLIENT_ID,
            'redirect_uri' => self::REDIRECT,
            'state' => 'st-123',
            'username' => $username,
            'password' => HubnetSeedAccounts::PASSWORD,
            'type' => $type->value,
        ]);

        $location = (string) $response->headers->get('Location');
        $this->assertStringContainsString('code=', $location, 'login tidak menghasilkan code');
        $this->assertStringContainsString('state=st-123', $location);

        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        return (string) ($query['code'] ?? '');
    }

    private function tokenRequest(string $code): TestResponse
    {
        return $this->postJson('/sso/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::SECRET,
            'redirect_uri' => self::REDIRECT,
        ]);
    }

    private function exchange(string $code): string
    {
        $response = $this->tokenRequest($code)->assertOk()->assertJsonPath('token_type', 'Bearer');

        return (string) $response->json('access_token');
    }
}
