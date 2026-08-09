<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Klien OAuth terdaftar untuk SSO Hubnet TIRUAN.
 *
 * @property int $id
 * @property string $name
 * @property string $client_id
 * @property string $client_secret
 * @property array<int, string> $redirect_uris
 * @property bool $is_active
 */
class HubnetOAuthClient extends Model
{
    // Tanpa ini Eloquent menebak `hubnet_o_auth_clients` (memenggal "OAuth").
    protected $table = 'hubnet_oauth_clients';

    protected $fillable = [
        'name',
        'client_id',
        'client_secret',
        'redirect_uris',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'redirect_uris' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<HubnetOAuthClient>  $query
     * @return Builder<HubnetOAuthClient>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
