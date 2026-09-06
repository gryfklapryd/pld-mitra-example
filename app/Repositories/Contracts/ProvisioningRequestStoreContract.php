<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

/**
 * Ingatan idempotensi `API Provisioning URL` (kontrak §7).
 *
 * Menyimpan JAWABAN, bukan sekadar penanda "sudah pernah diproses". Kontrak
 * meminta percobaan ulang dijawab sama persis dengan percobaan pertama, dan
 * jawaban itu memuat `userLogin` yang dipakai PLD untuk SSO — menyusunnya ulang
 * dari keadaan sekarang bisa menghasilkan nilai berbeda bila member sempat
 * disunting di antara dua percobaan.
 */
interface ProvisioningRequestStoreContract
{
    /**
     * Jawaban yang pernah diberikan untuk `requestId` ini, atau null bila baru.
     *
     * @return array{response: array<string, mixed>, status: int}|null
     */
    public function find(string $requestId): ?array;

    /**
     * Menyimpan jawaban untuk `requestId`.
     *
     * Mengembalikan jawaban yang BERLAKU: bila di antara pemeriksaan dan
     * penyimpanan ada percobaan kembar yang menang duluan, yang dikembalikan
     * adalah milik pemenang, bukan milik pemanggil ini. Tanpa itu, dua percobaan
     * yang tiba berbarengan sama-sama lolos pemeriksaan lalu sama-sama membuat
     * akun — persis yang hendak dicegah kunci idempotensi.
     *
     * @param  array<string, mixed>  $response
     * @return array{response: array<string, mixed>, status: int}
     */
    public function remember(string $requestId, string $action, ?string $pldUserId, array $response, int $status): array;
}
