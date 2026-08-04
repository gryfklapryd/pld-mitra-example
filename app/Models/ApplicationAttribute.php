<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pasangan keterangan khas domain — `attributes[]` kontrak §4.6.
 *
 * @property int $id
 * @property int $application_id
 * @property string $label
 * @property string $value
 */
class ApplicationAttribute extends Model
{
    protected $table = 'application_attributes';

    protected $fillable = [
        'application_id',
        'label',
        'value',
    ];

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
