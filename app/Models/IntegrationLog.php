<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Jejak satu panggilan integrasi.
 *
 * @property int $id
 * @property string $direction
 * @property string $endpoint
 * @property int|null $http_status
 * @property array<string, mixed>|null $request_payload
 * @property array<string, mixed>|null $response_payload
 * @property string|null $error_message
 * @property int|null $duration_ms
 * @property string|null $remote_ip
 */
class IntegrationLog extends Model
{
    public const DIRECTION_INBOUND = 'INBOUND';

    public const DIRECTION_OUTBOUND = 'OUTBOUND';

    protected $fillable = [
        'direction',
        'endpoint',
        'http_status',
        'request_payload',
        'response_payload',
        'error_message',
        'duration_ms',
        'remote_ip',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
            'http_status' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    /**
     * @param  Builder<IntegrationLog>  $query
     * @return Builder<IntegrationLog>
     */
    public function scopeInbound(Builder $query): Builder
    {
        return $query->where('direction', self::DIRECTION_INBOUND);
    }

    /**
     * @param  Builder<IntegrationLog>  $query
     * @return Builder<IntegrationLog>
     */
    public function scopeOutbound(Builder $query): Builder
    {
        return $query->where('direction', self::DIRECTION_OUTBOUND);
    }

    public function isSuccessful(): bool
    {
        return $this->http_status !== null && $this->http_status >= 200 && $this->http_status < 300;
    }
}
