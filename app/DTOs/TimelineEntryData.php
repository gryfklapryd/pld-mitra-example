<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\StageStatus;
use Illuminate\Support\Carbon;

/**
 * Satu entri `timeline[]` — kontrak §4.2.
 */
final readonly class TimelineEntryData
{
    public function __construct(
        public int $stage,
        public string $name,
        public StageStatus $status,
        public ?Carbon $occurredAt,
        public ?string $actor,
        public ?string $note,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toContractArray(): array
    {
        return [
            'stage' => $this->stage,
            'name' => $this->name,
            'status' => $this->status->value,
            // PENDING wajib null; DONE/CURRENT wajib terisi. Penegakannya ada di
            // TrackingPayloadService yang membangun objek ini — di sini cukup
            // diserialkan apa adanya.
            'occurredAt' => $this->occurredAt?->toIso8601ZuluString(),
            'actor' => $this->actor,
            'note' => $this->note,
        ];
    }
}
