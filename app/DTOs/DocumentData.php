<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Support\Carbon;

/**
 * Satu entri `documents[]` — kontrak §4.4. Tautan, tidak pernah isi berkas.
 */
final readonly class DocumentData
{
    public function __construct(
        public string $label,
        public string $url,
        public ?Carbon $issuedAt,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toContractArray(): array
    {
        return [
            'label' => $this->label,
            'url' => $this->url,
            'issuedAt' => $this->issuedAt?->toIso8601ZuluString(),
        ];
    }
}
