<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Satu pasangan `attributes[]` — kontrak §4.6.
 */
final readonly class AttributeData
{
    public function __construct(
        public string $label,
        public string $value,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toContractArray(): array
    {
        return [
            'label' => $this->label,
            'value' => $this->value,
        ];
    }
}
