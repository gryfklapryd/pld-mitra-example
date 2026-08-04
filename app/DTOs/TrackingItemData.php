<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\TrackingCategory;
use Illuminate\Support\Carbon;

/**
 * Satu entri `items[]` — kontrak §4.
 *
 * Bentuk jawaban bersifat TETAP (§4.7): nama field tidak boleh diganti dan field
 * karangan sendiri diabaikan PLD tanpa galat. Karena itu serialisasinya dikurung
 * di satu method di sini, bukan disebar sebagai array literal di controller —
 * satu tempat untuk diperiksa saat versi kontrak naik.
 */
final readonly class TrackingItemData
{
    /**
     * @param  array<int, TimelineEntryData>  $timeline
     * @param  array<int, DocumentData>  $documents
     * @param  array<int, AttributeData>  $attributes
     */
    public function __construct(
        public string $userLogin,
        public string $externalRef,
        public string $label,
        public TrackingCategory $category,
        public string $statusLabel,
        public Carbon $submittedAt,
        public Carbon $updatedAt,
        public ?Carbon $expiresAt,
        public string $detailUrl,
        public int $currentStage,
        public int $totalStages,
        public array $timeline,
        public ?ActionRequiredData $actionRequired,
        public array $documents,
        public array $attributes,
        public ?ContactData $contact,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toContractArray(): array
    {
        return [
            'userLogin' => $this->userLogin,
            'externalRef' => $this->externalRef,
            'label' => $this->label,
            'category' => $this->category->value,
            'statusLabel' => $this->statusLabel,
            'submittedAt' => $this->submittedAt->toIso8601ZuluString(),
            'updatedAt' => $this->updatedAt->toIso8601ZuluString(),
            'expiresAt' => $this->expiresAt?->toIso8601ZuluString(),
            'detailUrl' => $this->detailUrl,
            'currentStage' => $this->currentStage,
            'totalStages' => $this->totalStages,
            'timeline' => array_map(
                static fn (TimelineEntryData $e): array => $e->toContractArray(),
                $this->timeline,
            ),
            // null, bukan dihilangkan: kontrak menyebut "wajib null selain
            // ACTION_REQUIRED", dan PLD memang membedakan null dari objek kosong.
            'actionRequired' => $this->actionRequired?->toContractArray(),
            'documents' => array_map(
                static fn (DocumentData $d): array => $d->toContractArray(),
                $this->documents,
            ),
            'attributes' => array_map(
                static fn (AttributeData $a): array => $a->toContractArray(),
                $this->attributes,
            ),
            'contact' => $this->contact?->toContractArray(),
        ];
    }
}
