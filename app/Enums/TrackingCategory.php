<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Tujuh kategori kontrak §5 — daftar TERTUTUP.
 *
 * PLD menolak nilai di luar daftar ini per item, jadi mengetiknya sebagai string
 * bebas di sekujur aplikasi adalah cara paling mudah kehilangan satu permohonan
 * karena salah ketik. Dijadikan enum supaya salah nilai gagal saat kompilasi/uji,
 * bukan saat sinkronisasi jam 2 pagi.
 *
 * Pemetaan status internal Anda ke tujuh nilai ini adalah INTI standardisasinya:
 * dari sinilah PLD tahu apa yang boleh diwarnai, diurutkan, dan — sejak adanya
 * notifikasi — apa yang layak dikirim sebagai email.
 */
enum TrackingCategory: string
{
    /** Sedang diproses di pihak kita. Member menunggu, tak perlu berbuat apa-apa. */
    case InProgress = 'IN_PROGRESS';

    /** Menunggu tindakan MEMBER — melengkapi berkas, membayar, menandatangani. */
    case ActionRequired = 'ACTION_REQUIRED';

    /** Selesai, hasil sudah terbit. */
    case Completed = 'COMPLETED';

    /** Ditolak pengelola layanan. */
    case Rejected = 'REJECTED';

    /** Dibatalkan oleh member sendiri. */
    case Cancelled = 'CANCELLED';

    /** Pernah terbit, masa berlakunya habis. */
    case Expired = 'EXPIRED';

    /** Pernah terbit, dicabut sebelum masa berlaku habis. */
    case Revoked = 'REVOKED';

    /**
     * Proses sudah berakhir — tak ada lagi tahap yang sedang dikerjakan.
     *
     * Dipakai untuk menegakkan aturan kontrak "item terminal tidak boleh punya
     * tahap CURRENT". Melanggarnya membuat SELURUH item dibuang PLD.
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::Completed, self::Rejected, self::Cancelled, self::Expired, self::Revoked => true,
            self::InProgress, self::ActionRequired => false,
        };
    }

    /**
     * Menuntut member berbuat sesuatu.
     *
     * Satu-satunya kategori yang mewajibkan `actionRequired` terisi — dan
     * satu-satunya yang haram terisi bila kategorinya bukan ini.
     */
    public function requiresAction(): bool
    {
        return $this === self::ActionRequired;
    }

    /** Label Bahasa Indonesia untuk UI aplikasi ini sendiri (bukan bagian kontrak). */
    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'Sedang diproses',
            self::ActionRequired => 'Perlu tindakan member',
            self::Completed => 'Selesai',
            self::Rejected => 'Ditolak',
            self::Cancelled => 'Dibatalkan',
            self::Expired => 'Masa berlaku habis',
            self::Revoked => 'Dicabut',
        };
    }

    /** Kelas Tailwind untuk badge di UI admin. */
    public function badgeClass(): string
    {
        return match ($this) {
            self::InProgress => 'bg-blue-100 text-blue-800 ring-blue-600/20',
            self::ActionRequired => 'bg-amber-100 text-amber-900 ring-amber-600/20',
            self::Completed => 'bg-green-100 text-green-800 ring-green-600/20',
            self::Rejected, self::Revoked => 'bg-red-100 text-red-800 ring-red-600/20',
            self::Cancelled => 'bg-gray-100 text-gray-700 ring-gray-500/20',
            self::Expired => 'bg-orange-100 text-orange-800 ring-orange-600/20',
        };
    }

    /** @return array<int, self> */
    public static function terminal(): array
    {
        return array_values(array_filter(self::cases(), static fn (self $c): bool => $c->isTerminal()));
    }
}
