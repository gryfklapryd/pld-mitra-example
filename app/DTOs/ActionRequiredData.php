<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Support\Carbon;

/**
 * `actionRequired` — kontrak §4.3.
 *
 * Objek ini hanya boleh lahir bila kategorinya ACTION_REQUIRED. Kontrak menegakkan
 * aturan DUA ARAH, dan arah kedualah yang sering terlewat: mengirim actionRequired
 * pada item yang kategorinya bukan ACTION_REQUIRED membuat SELURUH item ditolak.
 */
final readonly class ActionRequiredData
{
    public function __construct(
        public string $instruction,
        public ?Carbon $dueAt,
        public ?string $actionUrl,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toContractArray(): array
    {
        return [
            'instruction' => $this->instruction,
            'dueAt' => $this->dueAt?->toIso8601ZuluString(),
            'actionUrl' => $this->actionUrl,
        ];
    }
}
