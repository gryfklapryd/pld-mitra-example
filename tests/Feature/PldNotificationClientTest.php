<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTOs\PublishNotificationData;
use App\Models\IntegrationLog;
use App\Services\Contracts\PldNotificationClientContract;
use App\Services\PublishResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Jalur B — aplikasi ini memanggil `POST /notif/publish` milik PLD.
 */
final class PldNotificationClientTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'https://pld.test/user/api/v1/notif/publish';

    #[Test]
    public function mengirim_service_key_pada_header_dan_membaca_202(): void
    {
        Http::fake([self::ENDPOINT => Http::response([
            'status' => '00',
            'message' => 'success',
            'data' => ['channels' => ['EMAIL', 'INAPP'], 'status' => 'ACCEPTED'],
            'version' => 'v1',
        ], 202)]);

        $result = $this->publish();

        $this->assertTrue($result->accepted);
        $this->assertSame(['EMAIL', 'INAPP'], $result->channels);

        Http::assertSent(static fn ($request): bool => $request->hasHeader('X-Service-Key', 'svc_uji.rahasia-uji')
            && $request['recipient']['email'] === 'budi@example.go.id');
    }

    #[Test]
    public function tidak_pernah_mengirim_alamat_email_tujuan_atau_asal_layanan(): void
    {
        // Tiga hal yang sengaja tidak ada di badan permintaan (kontrak §4.2):
        // alamat kirim, asal layanan, dan host tombol aksi. Bila salah satunya
        // ikut terkirim, PLD menjadi relay email terbuka atas nama AviasiHub.
        Http::fake([self::ENDPOINT => Http::response(['data' => ['channels' => []]], 202)]);

        $this->publish();

        Http::assertSent(static function ($request): bool {
            $body = $request->data();

            return ! array_key_exists('serviceId', $body)
                && ! array_key_exists('to', $body)
                && ! array_key_exists('from', $body)
                && ! array_key_exists('host', $body);
        });
    }

    #[Test]
    public function tepat_satu_penerima_yang_dikirim(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['data' => ['channels' => []]], 202)]);

        $this->publish();

        Http::assertSent(static function ($request): bool {
            $recipient = $request['recipient'];

            return count($recipient) === 1 && array_key_exists('email', $recipient);
        });
    }

    #[Test]
    public function status_422_bukan_kegagalan_yang_layak_diulang(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['status' => '02'], 422)]);

        $result = $this->publish();

        $this->assertFalse($result->accepted);
        $this->assertFalse($result->isRetryable());
        $this->assertStringContainsString('akses APPROVED', $result->message);
    }

    #[Test]
    public function status_429_layak_diulang_dengan_jeda(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['status' => '02'], 429)]);

        $result = $this->publish();

        $this->assertFalse($result->accepted);
        $this->assertTrue($result->isRetryable());
    }

    #[Test]
    public function status_400_tidak_layak_diulang_dan_menyebut_action_path(): void
    {
        // 400 hampir selalu berarti actionPath dikirim sebagai URL lengkap.
        // Mengulanginya hanya memindahkan bug kita menjadi beban di sisi PLD.
        Http::fake([self::ENDPOINT => Http::response(['status' => '02'], 400)]);

        $result = $this->publish();

        $this->assertFalse($result->isRetryable());
        $this->assertStringContainsString('PATH RELATIF', $result->message);
    }

    #[Test]
    public function kegagalan_jaringan_tidak_melempar_exception(): void
    {
        // Notifikasi adalah efek samping. Pekerjaan utama tidak boleh gagal hanya
        // karena pemberitahuannya tidak terkirim.
        Http::fake(fn () => throw new ConnectionException('DNS gagal'));

        $result = $this->publish();

        $this->assertFalse($result->accepted);
        $this->assertNull($result->status);
        $this->assertTrue($result->isRetryable());
    }

    #[Test]
    public function panggilan_keluar_tercatat_di_log_integrasi(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['data' => ['channels' => ['EMAIL']]], 202)]);

        $this->publish();

        $log = IntegrationLog::query()->outbound()->latest('id')->firstOrFail();

        $this->assertSame(202, $log->http_status);
        $this->assertSame(self::ENDPOINT, $log->endpoint);
    }

    private function publish(): PublishResult
    {
        return app(PldNotificationClientContract::class)->publish(
            PublishNotificationData::toEmail(
                email: 'budi@example.go.id',
                eventType: 'PENGUMUMAN_PEMELIHARAAN',
                title: 'Pemeliharaan Terjadwal',
                message: 'Aplikasi tidak dapat diakses malam ini.',
                actionPath: '/pengumuman/2026-08-03',
            ),
        );
    }
}
