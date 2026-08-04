<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\DTOs\PublishNotificationData;
use App\Services\PublishResult;

interface PldNotificationClientContract
{
    /**
     * Kirim satu notifikasi lewat Jalur B (`POST /notif/publish`).
     *
     * TIDAK melempar exception untuk kegagalan yang wajar (401/422/429/jaringan).
     * Notifikasi adalah efek samping dari pekerjaan utama — permohonan yang sudah
     * disetujui tidak boleh gagal hanya karena pemberitahuannya tidak terkirim.
     * Pemanggil memutuskan sendiri apa yang layak dicoba ulang.
     */
    public function publish(PublishNotificationData $data): PublishResult;

    /** Apakah kredensial Jalur B sudah dikonfigurasi. */
    public function isConfigured(): bool;
}
