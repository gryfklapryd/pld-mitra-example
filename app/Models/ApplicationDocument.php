<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Tautan dokumen hasil proses — `documents[]` kontrak §4.4.
 *
 * @property int $id
 * @property int $application_id
 * @property string $label
 * @property string $url
 * @property Carbon|null $issued_at
 */
class ApplicationDocument extends Model
{
    protected $fillable = [
        'application_id',
        'label',
        'url',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
