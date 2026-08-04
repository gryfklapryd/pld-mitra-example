<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\IntegrationLog;

interface IntegrationLoggerContract
{
    /**
     * Catat panggilan MASUK (PLD → aplikasi ini).
     *
     * @param  array<mixed>  $request
     * @param  array<mixed>  $response
     */
    public function inbound(
        string $endpoint,
        array $request,
        array $response,
        int $status,
        ?string $remoteIp,
        ?int $durationMs = null,
    ): IntegrationLog;

    /**
     * Catat panggilan KELUAR (aplikasi ini → PLD).
     *
     * @param  array<mixed>  $request
     * @param  array<mixed>|null  $response
     */
    public function outbound(
        string $endpoint,
        array $request,
        ?array $response,
        ?int $status,
        ?string $error = null,
        ?int $durationMs = null,
    ): IntegrationLog;
}
