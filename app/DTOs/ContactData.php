<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * `contact` — kontrak §4.5.
 *
 * Unit atau jabatan tempat member bertanya. Kontrak melarang identitas
 * perorangan di sini: isinya tampil di portal PLD dan ikut ke email member,
 * jadi nama pegawai yang pindah tugas akan terus menerima telepon selamanya.
 */
final readonly class ContactData
{
    public function __construct(
        public string $unit,
        public ?string $email,
        public ?string $phone,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toContractArray(): array
    {
        return [
            'unit' => $this->unit,
            'email' => $this->email,
            'phone' => $this->phone,
        ];
    }
}
