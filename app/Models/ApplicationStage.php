<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Satu tahap pada perjalanan proses.
 *
 * Tidak punya `status` — lihat migrasi tabelnya untuk alasannya.
 *
 * @property int $id
 * @property int $application_id
 * @property int $stage
 * @property string $name
 * @property Carbon|null $occurred_at
 * @property string|null $actor
 * @property string|null $note
 */
class ApplicationStage extends Model
{
    protected $fillable = [
        'application_id',
        'stage',
        'name',
        'occurred_at',
        'actor',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'stage' => 'integer',
        ];
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
