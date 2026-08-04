<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Application;
use App\Models\Member;
use App\Models\User;

/**
 * Siapa boleh membaca satu proses.
 *
 * Halaman yang dijaga policy ini adalah tujuan `detailUrl` — URL yang kita
 * kirimkan sendiri ke PLD dan yang muncul sebagai tombol "Buka di layanan" di
 * portal member.
 *
 * Sebelum ada policy ini halaman itu terbuka bagi siapa saja yang tahu
 * `external_ref`-nya. Nomor seperti `PRZ-2026-00123` berurutan dan tercetak di
 * surat, jadi "tahu ref-nya" bukan penghalang: satu orang yang punya satu nomor
 * bisa menelusuri seluruh permohonan tahun itu, lengkap dengan nama unit
 * penangan, catatan penolakan, dan tautan dokumen hasil.
 */
final class ApplicationPolicy
{
    /**
     * Member hanya boleh membaca prosesnya sendiri.
     */
    public function view(Member $member, Application $application): bool
    {
        return $member->id === $application->member_id;
    }

    /**
     * Operator internal boleh membaca proses siapa pun — itu memang pekerjaannya.
     *
     * Method terpisah, bukan cabang `if` di dalam view(): keduanya menjawab
     * pertanyaan berbeda, dan menyatukannya berarti satu perubahan pada aturan
     * member diam-diam ikut mengubah kewenangan operator.
     */
    public function viewAsOperator(User $user, Application $application): bool
    {
        return true;
    }
}
