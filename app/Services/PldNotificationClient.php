<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\PublishNotificationData;
use App\Services\Contracts\IntegrationLoggerContract;
use App\Services\Contracts\PldNotificationClientContract;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Throwable;

/**
 * Klien Jalur B — aplikasi ini memanggil `POST /notif/publish` milik PLD.
 *
 * Dipakai untuk pemberitahuan DI LUAR proses bertahap: pengumuman pemeliharaan,
 * akun ditangguhkan, undangan. Untuk perubahan status permohonan, JANGAN pakai
 * jalur ini — Jalur A (tracking) sudah menerbitkan notifikasinya sendiri, dan
 * memakai keduanya untuk peristiwa yang sama menghasilkan notifikasi ganda.
 * Pembagian itu belum dijaga oleh PLD, jadi ia sepenuhnya tanggung jawab kita.
 */
final readonly class PldNotificationClient implements PldNotificationClientContract
{
    public function __construct(
        private HttpFactory $http,
        private IntegrationLoggerContract $logger,
    ) {}

    public function isConfigured(): bool
    {
        return $this->baseUrl() !== '' && $this->serviceKey() !== '';
    }

    public function publish(PublishNotificationData $data): PublishResult
    {
        if (! $this->isConfigured()) {
            return PublishResult::unreachable(
                'Jalur B belum dikonfigurasi. Isi PLD_BASE_URL dan PLD_SERVICE_KEY di .env.',
            );
        }

        $endpoint = $this->baseUrl().'/user/api/v1/notif/publish';
        $payload = $data->toRequestArray();
        $startedAt = microtime(true);

        try {
            $response = $this->http
                ->timeout((int) config('pld.publish.timeout'))
                ->withHeaders(['X-Service-Key' => $this->serviceKey()])
                ->acceptJson()
                ->asJson()
                ->post($endpoint, $payload);
        } catch (ConnectionException|Throwable $e) {
            $this->logger->outbound(
                endpoint: $endpoint,
                request: $payload,
                response: null,
                status: null,
                error: $e->getMessage(),
                durationMs: $this->elapsedMs($startedAt),
            );

            return PublishResult::unreachable('PLD tidak dapat dihubungi: '.$e->getMessage());
        }

        $durationMs = $this->elapsedMs($startedAt);
        $body = $this->decode($response->body());

        $this->logger->outbound(
            endpoint: $endpoint,
            request: $payload,
            response: $body,
            status: $response->status(),
            error: $response->successful() ? null : $response->body(),
            durationMs: $durationMs,
        );

        // Keberhasilan dinilai dari KODE HTTP, bukan dari amplop status/message —
        // kontrak menyebutnya eksplisit, dan amplop itu hanya mengikuti kode.
        if ($response->status() === 202) {
            /** @var array<int, string> $channels */
            $channels = $body['data']['channels'] ?? [];

            return PublishResult::accepted($channels);
        }

        return PublishResult::rejected(
            $response->status(),
            $this->explain($response->status(), $body),
        );
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function explain(int $status, array $body): string
    {
        return match ($status) {
            400 => 'Payload ditolak PLD. Periksa actionPath — ia harus PATH RELATIF (mis. /permohonan/123), bukan URL lengkap.',
            401 => 'Service Key tidak berlaku (salah, dicabut, atau layanan dinonaktifkan).',
            409 => 'Nilai channels tidak dikenal. Gunakan EMAIL dan/atau INAPP.',
            422 => 'Penerima tidak dikenal PLD atau belum punya akses APPROVED ke layanan ini. Ini bukan galat sistem.',
            429 => 'Batas laju terlampaui. Tunggu dengan jeda membesar (1, 2, 4 menit) — jangan coba ulang rapat.',
            default => is_string($body['data'] ?? null)
                ? $body['data']
                : 'PLD menjawab status '.$status.'.',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $body): array
    {
        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return ['raw' => mb_substr($body, 0, 1000)];
        }

        return is_array($decoded) ? $decoded : ['raw' => $body];
    }

    private function elapsedMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    private function baseUrl(): string
    {
        return (string) config('pld.publish.base_url');
    }

    private function serviceKey(): string
    {
        return (string) config('pld.publish.service_key');
    }
}
