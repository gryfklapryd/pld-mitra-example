<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Status satu tahap pada `timeline[]` — kontrak §4.2.
 *
 * Nilai ini TIDAK PERNAH disimpan di basis data; ia diturunkan saat menyusun
 * payload (lihat App\Services\TrackingPayloadService::stageStatusFor). Enum ini
 * ada supaya bentuk turunannya punya nama, bukan supaya ada kolomnya.
 */
enum StageStatus: string
{
    /** Sudah lewat. Wajib punya `occurredAt`. */
    case Done = 'DONE';

    /** Sedang berjalan. Wajib punya `occurredAt`, dan hanya boleh ADA SATU per item. */
    case Current = 'CURRENT';

    /** Belum tercapai. `occurredAt` null. */
    case Pending = 'PENDING';

    /** Kontrak mewajibkan `occurredAt` terisi untuk DONE dan CURRENT. */
    public function requiresOccurredAt(): bool
    {
        return $this !== self::Pending;
    }
}
