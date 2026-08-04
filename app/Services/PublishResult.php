<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Hasil satu panggilan Jalur B.
 *
 * Kode HTTP disimpan apa adanya karena kontrak §4.2 memberi arti berbeda pada
 * tiap nilai, dan meleburnya menjadi boolean menghapus justru informasi yang
 * menentukan tindakan berikutnya:
 *
 *   202 → diterima & dijadwalkan (BUKAN "sudah sampai" — email masih mengantre)
 *   400 → payload cacat / actionPath bukan path relatif → perbaiki kode, jangan ulangi
 *   401 → Service Key salah/dicabut → perbaiki konfigurasi, jangan ulangi
 *   422 → penerima tak dikenal ATAU tak punya akses APPROVED → bukan galat sistem
 *   429 → batas laju → tunggu dengan jeda membesar
 */
final readonly class PublishResult
{
    /**
     * @param  array<int, string>  $channels
     */
    private function __construct(
        public bool $accepted,
        public ?int $status,
        public string $message,
        public array $channels = [],
    ) {}

    /**
     * @param  array<int, string>  $channels
     */
    public static function accepted(array $channels): self
    {
        return new self(true, 202, 'Notifikasi diterima dan dijadwalkan PLD.', $channels);
    }

    public static function rejected(int $status, string $message): self
    {
        return new self(false, $status, $message);
    }

    /** Gagal sebelum sempat mendapat jawaban (DNS, timeout, TLS). */
    public static function unreachable(string $message): self
    {
        return new self(false, null, $message);
    }

    /**
     * Layak dicoba ulang?
     *
     * Hanya 429 dan kegagalan jaringan. 400/401 adalah kesalahan kita sendiri —
     * mengulanginya hanya memindahkan bug menjadi beban di sisi PLD.
     */
    public function isRetryable(): bool
    {
        return $this->status === 429 || $this->status === null || $this->status >= 500;
    }
}
