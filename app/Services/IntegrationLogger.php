<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\IntegrationLog;
use App\Repositories\Contracts\IntegrationLogRepositoryContract;
use App\Services\Contracts\IntegrationLoggerContract;
use App\Support\PayloadRedactor;

/**
 * Menulis jejak panggilan integrasi, dengan penyamaran nilai sensitif.
 *
 * Penyamaran dilakukan DI SINI, satu pintu, bukan di tiap pemanggil: pemanggil
 * yang lupa menyamarkan tidak menghasilkan galat apa pun — hanya baris log berisi
 * password bersih yang tak seorang pun sadari sampai terlambat.
 */
final readonly class IntegrationLogger implements IntegrationLoggerContract
{
    public function __construct(
        private IntegrationLogRepositoryContract $logs,
    ) {}

    public function inbound(
        string $endpoint,
        array $request,
        array $response,
        int $status,
        ?string $remoteIp,
        ?int $durationMs = null,
    ): IntegrationLog {
        return $this->logs->record([
            'direction' => IntegrationLog::DIRECTION_INBOUND,
            'endpoint' => $endpoint,
            'http_status' => $status,
            'request_payload' => PayloadRedactor::redact($request),
            'response_payload' => PayloadRedactor::redact($response),
            'remote_ip' => $remoteIp,
            'duration_ms' => $durationMs,
        ]);
    }

    public function outbound(
        string $endpoint,
        array $request,
        ?array $response,
        ?int $status,
        ?string $error = null,
        ?int $durationMs = null,
    ): IntegrationLog {
        return $this->logs->record([
            'direction' => IntegrationLog::DIRECTION_OUTBOUND,
            'endpoint' => $endpoint,
            'http_status' => $status,
            'request_payload' => PayloadRedactor::redact($request),
            'response_payload' => $response === null ? null : PayloadRedactor::redact($response),
            'error_message' => $error === null ? null : mb_substr($error, 0, 500),
            'duration_ms' => $durationMs,
        ]);
    }
}
