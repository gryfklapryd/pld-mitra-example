<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\DTOs\TrackingItemData;
use App\DTOs\TrackingRequestData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TrackingRequest;
use App\Services\Contracts\IntegrationLoggerContract;
use App\Services\Contracts\TrackingPayloadServiceContract;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * `API Tracking URL` — kontrak §3–§4.
 *
 * Jawaban adalah GAMBARAN UTUH DAN TERKINI untuk member yang diminta, bukan
 * selisih. Proses yang berhenti dikirim akan hilang dari PLD (§7), jadi item
 * terminal tetap dikirim selama masih relevan bagi member.
 *
 * Satu hal yang menentukan pengalaman member dan mudah salah: bedanya antara
 * "menjawab 200 dengan items kosong" dan "gagal menjawab". Yang pertama dibaca
 * PLD sebagai pernyataan yang dipercaya ("member ini memang tak punya proses")
 * dan MENGGANTI cache. Yang kedua membuat PLD mempertahankan data terakhir yang
 * berhasil. Karena itu kegagalan internal di sini WAJIB dibalas 500 — membalas
 * 200 dengan items kosong akan menghapus seluruh riwayat member dari portal.
 */
final class TrackingController extends Controller
{
    public function __construct(
        private readonly TrackingPayloadServiceContract $payload,
        private readonly IntegrationLoggerContract $logger,
    ) {}

    public function __invoke(TrackingRequest $request): JsonResponse
    {
        $startedAt = microtime(true);

        try {
            $items = $this->payload->build(TrackingRequestData::fromRequest($request));

            $body = [
                'contractVersion' => (string) config('pld.contract_version'),
                'items' => array_map(
                    static fn (TrackingItemData $item): array => $item->toContractArray(),
                    $items,
                ),
            ];

            $status = Response::HTTP_OK;
        } catch (Throwable $e) {
            report($e);

            // 500, BUKAN 200 dengan items kosong. Lihat catatan kelas di atas —
            // membalas kosong di sini berarti menghapus riwayat member dari portal
            // PLD hanya karena satu galat sesaat di sisi kita.
            $body = ['message' => 'Terjadi kesalahan sistem. Silakan coba beberapa saat lagi.'];
            $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        $this->logger->inbound(
            endpoint: 'API Tracking URL',
            request: $request->all(),
            response: $body,
            status: $status,
            remoteIp: $request->ip(),
            durationMs: (int) round((microtime(true) - $startedAt) * 1000),
        );

        return new JsonResponse($body, $status);
    }
}
