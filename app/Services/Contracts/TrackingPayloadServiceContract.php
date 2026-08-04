<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\DTOs\TrackingItemData;
use App\DTOs\TrackingRequestData;
use App\Models\Application;

interface TrackingPayloadServiceContract
{
    /**
     * Susun jawaban tracking untuk daftar `userLogin` yang diminta PLD.
     *
     * @return array<int, TrackingItemData>
     */
    public function build(TrackingRequestData $request): array;

    /**
     * Serialkan satu proses menjadi item kontrak.
     *
     * Dipisah supaya UI admin bisa menampilkan pratinjau payload persis seperti
     * yang akan diterima PLD — cara tercepat menemukan salah pemetaan sebelum
     * sinkronisasi berikutnya membawanya ke layar member.
     */
    public function buildItem(Application $application): TrackingItemData;
}
