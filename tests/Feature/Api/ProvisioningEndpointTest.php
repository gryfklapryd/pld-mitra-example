<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `API Provisioning URL` — kontrak provisioning v1.0.
 *
 * Tiap cabang jawaban diuji satu per satu, karena masing-masing memicu tindakan
 * yang berbeda di sisi PLD: menyimpan userLogin, melempar member ke "Tautkan
 * Akun", berhenti dan menunggu manusia, atau mengulang nanti. Salah satu cabang
 * yang keliru tidak menampakkan diri sebagai galat — ia menampakkan diri sebagai
 * member yang tak bisa masuk, atau akun orang lain yang diserahkan.
 */
final class ProvisioningEndpointTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'kunci-uji-integrasi-pld';

    private const PLD_USER = '0F8B3D21-5C44-4C8E-9A76-3D2B91E77A10';

    /**
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function payload(array $override = []): array
    {
        return array_replace_recursive([
            'contractVersion' => '1.0',
            'action' => 'createAccount',
            'requestId' => (string) \Illuminate\Support\Str::uuid(),
            'subject' => [
                'pldUserId' => self::PLD_USER,
                'email' => 'budi.baru@dephub.go.id',
                'nip' => '198703152010121003',
                'fullName' => 'Budi Santoso',
                'accountKind' => 'INTERNAL',
            ],
            'roles' => ['PESERTA'],
        ], $override);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function kirim(array $payload): \Illuminate\Testing\TestResponse
    {
        return $this->withHeader('Api-Key', self::KEY)
            ->postJson('/api/pld/provisioning', $payload);
    }

    /**
     * 400, bukan 401 — mengikuti gerbang yang sama dengan tiga endpoint kontrak
     * yang lebih tua (VerifyPldApiKey). Di sisi PLD keduanya berarti hal yang
     * sama: berhenti, jangan diulang.
     */
    #[Test]
    public function tanpa_api_key_ditolak(): void
    {
        $this->postJson('/api/pld/provisioning', $this->payload())
            ->assertStatus(400)
            ->assertJson(['message' => 'Api-Key tidak valid.']);
    }

    #[Test]
    public function membuat_akun_baru_dan_mengembalikan_user_login(): void
    {
        $this->kirim($this->payload())
            ->assertOk()
            ->assertJson([
                'contractVersion' => '1.0',
                'status' => 'CREATED',
                // Pegawai memakai NIP sebagai identitas masuk di aplikasi ini —
                // PLD tak memaksakan formatnya dan justru menunggu kita
                // memberitahukan hasilnya lewat userLogin (kontrak §5).
                'userLogin' => '198703152010121003',
            ]);

        $member = Member::query()->where('pld_user_id', self::PLD_USER)->firstOrFail();

        $this->assertSame(['PESERTA'], $member->roles);
        $this->assertTrue($member->is_active);
        $this->assertNotNull($member->provisioned_at);
        // Lahir dari permintaan PLD, yang sudah memverifikasi emailnya saat registrasi.
        $this->assertNotNull($member->email_verified_at);
    }

    /**
     * Kasus paling sering di lapangan: member sudah punya akun di sini jauh
     * sebelum PLD mengenalnya, DAN emailnya tak pernah kita verifikasi.
     *
     * Menautkannya otomatis berarti menyerahkan akun ini kepada siapa pun yang
     * mendaftar di PLD memakai email yang sama.
     */
    #[Test]
    public function akun_lama_tanpa_verifikasi_email_menolak_ditautkan(): void
    {
        Member::query()->create([
            'user_login' => 'budi.lama',
            'name' => 'Budi Lama',
            'email' => 'budi.baru@dephub.go.id',
            'password' => 'rahasia123',
            'is_active' => true,
        ]);

        $response = $this->kirim($this->payload())->assertOk();

        $response->assertJson(['status' => 'EXISTS_UNVERIFIED']);

        // userLogin TIDAK boleh ikut: mengirimkannya berarti memberi tahu penanya
        // identitas akun orang lain di sistem ini.
        $this->assertArrayNotHasKey('userLogin', $response->json());

        // Dan akun lamanya tidak boleh diklaim diam-diam.
        $this->assertDatabaseMissing('members', [
            'user_login' => 'budi.lama',
            'pld_user_id' => self::PLD_USER,
        ]);
    }

    #[Test]
    public function akun_lama_yang_terverifikasi_boleh_ditautkan(): void
    {
        Member::query()->create([
            'user_login' => 'budi.lama',
            'name' => 'Budi Lama',
            'email' => 'budi.baru@dephub.go.id',
            'email_verified_at' => now(),
            'password' => 'rahasia123',
            'is_active' => true,
        ]);

        $this->kirim($this->payload())
            ->assertOk()
            ->assertJson([
                'status' => 'EXISTS_LINKED',
                'userLogin' => 'budi.lama',
            ]);

        $this->assertDatabaseHas('members', [
            'user_login' => 'budi.lama',
            'pld_user_id' => self::PLD_USER,
        ]);
    }

    #[Test]
    public function kode_peran_asing_ditolak_tanpa_membuat_akun(): void
    {
        $this->kirim($this->payload(['roles' => ['PESERTA', 'SUPER_ADMIN_PALSU']]))
            ->assertOk()
            ->assertJson(['status' => 'ROLE_REJECTED']);

        $this->assertDatabaseMissing('members', ['pld_user_id' => self::PLD_USER]);
    }

    /**
     * Inti kontrak §7. Urutan yang benar-benar terjadi di jaringan: kita membuat
     * akun, jawaban kita hilang, PLD mengulang dengan requestId yang SAMA.
     * Tanpa ingatan idempotensi, percobaan kedua membuat akun KEDUA.
     */
    #[Test]
    public function percobaan_ulang_dengan_request_id_sama_tidak_membuat_akun_kedua(): void
    {
        $payload = $this->payload();

        $pertama = $this->kirim($payload)->assertOk();
        $kedua = $this->kirim($payload)->assertOk();

        // assertEquals, bukan assertSame: kolom `response` bertipe JSON di MySQL,
        // dan MySQL menyimpan objek JSON dalam bentuk biner yang MENGURUTKAN ULANG
        // kuncinya. Isinya identik, urutan kuncinya tidak — dan yang dijanjikan
        // kontrak adalah jawaban yang sama, bukan byte yang sama.
        $this->assertEquals($pertama->json(), $kedua->json());
        $this->assertSame(1, Member::query()->where('pld_user_id', self::PLD_USER)->count());
    }

    #[Test]
    public function request_id_berbeda_untuk_orang_yang_sama_tidak_menggandakan_akun(): void
    {
        $this->kirim($this->payload())->assertOk();

        // Percobaan kedua dengan requestId baru — mis. PLD mengantrekan ulang
        // pekerjaan yang sama. Pencocokan lewat pldUserId yang menahannya.
        $this->kirim($this->payload())
            ->assertOk()
            ->assertJson(['status' => 'EXISTS_LINKED']);

        $this->assertSame(1, Member::query()->where('pld_user_id', self::PLD_USER)->count());
    }

    #[Test]
    public function set_status_menonaktifkan_tanpa_menghapus(): void
    {
        $this->kirim($this->payload())->assertOk();

        $this->kirim($this->payload([
            'action' => 'setStatus',
            'requestId' => 'REQ-NONAKTIF',
            'status' => 'DISABLED',
            'reason' => 'Yang bersangkutan pindah unit kerja.',
        ]))->assertOk()->assertJson(['status' => 'OK']);

        $member = Member::query()->where('pld_user_id', self::PLD_USER)->firstOrFail();
        $this->assertFalse($member->is_active);
        // Dinonaktifkan, BUKAN dihapus — akses bisa dipulihkan dan riwayatnya
        // harus tetap dapat ditelusuri (kontrak §4.1.2).
        $this->assertDatabaseHas('members', ['pld_user_id' => self::PLD_USER]);
    }

    /**
     * NOT_FOUND adalah KEBERHASILAN, bukan kegagalan (kontrak §4.1.2): akun baru
     * dibuat saat member pertama kali membuka layanan, jadi member yang aksesnya
     * dicabut lebih dulu memang tak punya akun di sini.
     */
    #[Test]
    public function set_status_untuk_member_tanpa_akun_menjawab_not_found_dengan_200(): void
    {
        $this->kirim($this->payload([
            'action' => 'setStatus',
            'requestId' => 'REQ-TAK-ADA',
            'status' => 'DISABLED',
        ]))->assertOk()->assertJson(['status' => 'NOT_FOUND']);
    }

    #[Test]
    public function set_role_mengganti_seluruh_daftar(): void
    {
        $this->kirim($this->payload(['roles' => ['PESERTA', 'PERSONEL']]))->assertOk();

        $ganti = $this->payload();
        $ganti['action'] = 'setRole';
        $ganti['requestId'] = 'REQ-GANTI-PERAN';
        $ganti['roles'] = ['INSPEKTUR'];

        $this->kirim($ganti)->assertOk()->assertJson(['status' => 'OK']);

        $member = Member::query()->where('pld_user_id', self::PLD_USER)->firstOrFail();
        // MENGGANTI, bukan menambah: PESERTA & PERSONEL harus lepas.
        $this->assertSame(['INSPEKTUR'], $member->roles);
    }

    /**
     * Daftar kosong adalah cara kontrak §4.1.3 melepas SELURUH peran seseorang.
     *
     * Payload-nya disusun manual, tidak lewat helper: array_replace_recursive
     * TIDAK mengosongkan array — menimpa ['PESERTA'] dengan [] menghasilkan
     * ['PESERTA'] lagi, dan ujinya lolos tanpa pernah menguji apa pun.
     */
    #[Test]
    public function set_role_dengan_daftar_kosong_melepas_semua_peran(): void
    {
        $this->kirim($this->payload(['roles' => ['PESERTA']]))->assertOk();

        $kosongkan = $this->payload();
        $kosongkan['action'] = 'setRole';
        $kosongkan['requestId'] = 'REQ-KOSONGKAN';
        $kosongkan['roles'] = [];

        $this->kirim($kosongkan)->assertOk()->assertJson(['status' => 'OK']);

        $member = Member::query()->where('pld_user_id', self::PLD_USER)->firstOrFail();
        $this->assertSame([], $member->roles);
    }

    /**
     * 400, bukan 422. Kontrak §4.1.4 memetakan 400 ke "berhenti, jangan diulang";
     * 422 tak ada dalam peta itu dan akan dibaca pld-integration sebagai gangguan
     * sesaat, lalu diulang selamanya untuk sesuatu yang tak akan pernah berubah.
     */
    #[Test]
    public function permintaan_cacat_dijawab_400(): void
    {
        $payload = $this->payload();
        unset($payload['subject']['pldUserId']);

        $this->kirim($payload)->assertBadRequest();
    }

    #[Test]
    public function aksi_tak_dikenal_dijawab_400(): void
    {
        $this->kirim($this->payload(['action' => 'hapusAkun']))
            ->assertBadRequest();
    }

    /**
     * Kontrak §11: penambahan field opsional tidak menaikkan versi mayor, dan
     * implementasi wajib MENGABAIKAN field tak dikenal alih-alih menolaknya.
     * Menolaknya akan mematikan provisioning seluruh member sampai aplikasi ini
     * di-deploy ulang.
     */
    #[Test]
    public function field_tak_dikenal_diabaikan_bukan_ditolak(): void
    {
        $payload = $this->payload();
        $payload['fieldMasaDepan'] = 'apa saja';
        $payload['subject']['unitEselon'] = 'DNP';

        $this->kirim($payload)->assertOk()->assertJson(['status' => 'CREATED']);
    }
}
