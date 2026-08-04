<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TrackingCategory;
use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Satu proses milik member — dilaporkan ke PLD sebagai satu entri `items[]`.
 *
 * @property int $id
 * @property string $external_ref
 * @property int $member_id
 * @property string $label
 * @property TrackingCategory $category
 * @property string $status_label
 * @property Carbon $submitted_at
 * @property Carbon $status_changed_at
 * @property Carbon|null $expires_at
 * @property int $current_stage
 * @property int $total_stages
 * @property string|null $action_instruction
 * @property Carbon|null $action_due_at
 * @property string|null $action_url
 * @property string|null $contact_unit
 * @property string|null $contact_email
 * @property string|null $contact_phone
 */
class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory;

    protected $fillable = [
        'external_ref',
        'member_id',
        'label',
        'category',
        'status_label',
        'submitted_at',
        'status_changed_at',
        'expires_at',
        'current_stage',
        'total_stages',
        'action_instruction',
        'action_due_at',
        'action_url',
        'contact_unit',
        'contact_email',
        'contact_phone',
    ];

    protected function casts(): array
    {
        return [
            'category' => TrackingCategory::class,
            'submitted_at' => 'datetime',
            'status_changed_at' => 'datetime',
            'expires_at' => 'datetime',
            'action_due_at' => 'datetime',
            'current_stage' => 'integer',
            'total_stages' => 'integer',
        ];
    }

    /** @return BelongsTo<Member, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /** @return HasMany<ApplicationStage, $this> */
    public function stages(): HasMany
    {
        return $this->hasMany(ApplicationStage::class)->orderBy('stage');
    }

    /** @return HasMany<ApplicationDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    /** @return HasMany<ApplicationAttribute, $this> */
    public function attributes(): HasMany
    {
        return $this->hasMany(ApplicationAttribute::class);
    }

    /**
     * Proses yang masih boleh digerakkan.
     *
     * @param  Builder<Application>  $query
     * @return Builder<Application>
     */
    public function scopeRunning(Builder $query): Builder
    {
        return $query->whereIn('category', [
            TrackingCategory::InProgress->value,
            TrackingCategory::ActionRequired->value,
        ]);
    }

    public function isTerminal(): bool
    {
        return $this->category->isTerminal();
    }

    /**
     * `detailUrl` kontrak — WAJIB dan harus URL absolut.
     *
     * Diturunkan dari route, bukan disimpan: menyimpannya berarti seluruh baris
     * lama menunjuk ke domain lama begitu aplikasi ini pindah host, dan tautan
     * mati di portal PLD tidak menghasilkan galat apa pun yang bisa kita lihat —
     * hanya member yang menemukannya.
     */
    public function detailUrl(): string
    {
        return route('permohonan.show', ['externalRef' => $this->external_ref]);
    }
}
