<?php

declare(strict_types=1);

namespace Tests\Feature\Hubnet;

use App\Enums\HubnetUserType;
use App\Models\HubnetOAuthClient;
use App\Models\HubnetUser;
use App\Models\User;
use App\Support\HubnetSeedAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Klien OAuth berbasis DB (dikelola panel operator) + CRUD identitas & klien.
 *
 * Membuktikan sumber klien sudah pindah dari config ke basis data: SSO jalan
 * untuk klien yang HANYA ada di tabel, dan klien nonaktif ditolak.
 */
final class HubnetClientAndAdminTest extends TestCase
{
    use RefreshDatabase;

    private const REDIRECT = 'https://klien-db.test/hubnet/sso';

    protected function setUp(): void
    {
        parent::setUp();
        // Kosongkan config supaya yang teruji adalah jalur DB, bukan fallback env.
        config(['hubnet.client_id' => '', 'hubnet.client_secret' => '']);
    }

    #[Test]
    public function sso_jalan_untuk_klien_yang_hanya_terdaftar_di_db(): void
    {
        $client = $this->makeClient();
        $this->makeUser('199608082022031008');

        $code = $this->login($client->client_id, '199608082022031008');

        $this->postJson('/sso/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $client->client_id,
            'client_secret' => $client->client_secret,
            'redirect_uri' => self::REDIRECT,
        ])->assertOk()->assertJsonPath('token_type', 'Bearer');
    }

    #[Test]
    public function klien_nonaktif_ditolak_di_halaman_authorize(): void
    {
        $client = $this->makeClient(['is_active' => false]);

        $this->get('/sso/oauth/authorize?'.http_build_query([
            'client_id' => $client->client_id,
            'redirect_uri' => self::REDIRECT,
            'response_type' => 'code',
        ]))->assertOk()->assertSee('tidak dikenali', false);
    }

    #[Test]
    public function operator_bisa_membuat_identitas_hubnet(): void
    {
        $this->actingAs($this->operator());

        $this->post('/admin/hubnet-users', [
            'type' => HubnetUserType::Pegawai->value,
            'username' => '199999999999999999',
            'password' => 'rahasia-uji',
            'name' => 'PEGAWAI UJI',
            'nik' => '3201010101900001',
            'status' => '1',
        ])->assertRedirect(route('admin.hubnet-users.index'));

        $this->assertDatabaseHas('hubnet_users', [
            'type' => HubnetUserType::Pegawai->value,
            'username' => '199999999999999999',
        ]);
    }

    #[Test]
    public function operator_mendaftarkan_klien_dan_menerima_kredensial(): void
    {
        $this->actingAs($this->operator());

        $response = $this->post('/admin/hubnet-clients', [
            'name' => 'Aplikasi Uji',
            'redirect_uris' => "https://a.test/hubnet/sso\nhttps://b.test/hubnet/sso",
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.hubnet-clients.index'))->assertSessionHas('new_client');
        $this->assertDatabaseHas('hubnet_oauth_clients', ['name' => 'Aplikasi Uji']);

        $client = HubnetOAuthClient::query()->where('name', 'Aplikasi Uji')->firstOrFail();
        $this->assertCount(2, $client->redirect_uris);
        $this->assertNotEmpty($client->client_id);
        $this->assertNotEmpty($client->client_secret);
    }

    #[Test]
    public function tamu_tidak_bisa_masuk_panel_klien(): void
    {
        $this->get('/admin/hubnet-clients')->assertRedirect(route('operator.masuk'));
    }

    // --- pembantu ---------------------------------------------------------

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeClient(array $overrides = []): HubnetOAuthClient
    {
        return HubnetOAuthClient::query()->create(array_merge([
            'name' => 'Klien DB',
            'client_id' => 'db-'.bin2hex(random_bytes(6)),
            'client_secret' => bin2hex(random_bytes(16)),
            'redirect_uris' => [self::REDIRECT],
            'is_active' => true,
        ], $overrides));
    }

    private function makeUser(string $username): HubnetUser
    {
        return HubnetUser::query()->create([
            'type' => HubnetUserType::Pegawai,
            'username' => $username,
            'password' => HubnetSeedAccounts::PASSWORD,
            'name' => 'UJI',
            'nip' => $username,
            'nik' => '3514140808960004',
            'status' => true,
            'is_deleted' => false,
        ]);
    }

    private function login(string $clientId, string $username): string
    {
        $response = $this->post('/sso/oauth/authorize', [
            'client_id' => $clientId,
            'redirect_uri' => self::REDIRECT,
            'username' => $username,
            'password' => HubnetSeedAccounts::PASSWORD,
            'type' => HubnetUserType::Pegawai->value,
        ]);

        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

        return (string) ($query['code'] ?? '');
    }

    private function operator(): User
    {
        return User::query()->create([
            'name' => 'Operator Uji',
            'email' => 'op-'.bin2hex(random_bytes(4)).'@pel.test',
            'password' => 'rahasia123',
        ]);
    }
}
