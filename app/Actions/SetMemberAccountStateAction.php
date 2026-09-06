<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repositories\Contracts\MemberRepositoryContract;

/**
 * Aksi `setStatus` dan `setRole` — keduanya menyunting akun yang SUDAH ada.
 *
 * Digabung dalam satu kelas karena bentuknya identik: cari member lewat
 * pldUserId, lalu ubah satu aspeknya. Memisahkannya jadi dua kelas hanya akan
 * menggandakan pencarian dan penanganan "tidak ditemukan" yang sama persis.
 *
 * -------------------------------------------------------------------------
 * NOT_FOUND ADALAH KEBERHASILAN, BUKAN KEGAGALAN
 * -------------------------------------------------------------------------
 * Kontrak §4.1.2 menegaskannya, dan alasannya ada di sisi PLD: akun baru dibuat
 * saat member PERTAMA KALI membuka layanan. Member yang aksesnya dicabut sebelum
 * ia sempat membukanya memang tidak punya akun di sini — tak ada yang perlu
 * dikerjakan, dan tak ada yang perlu dilaporkan sebagai galat. Menjawab 500 di
 * situ akan memenuhi papan pantau PLD dengan kegagalan palsu, dan kegagalan
 * palsu yang cukup banyak membuat kegagalan sungguhan tak lagi terlihat.
 *
 * -------------------------------------------------------------------------
 * DINONAKTIFKAN, BUKAN DIHAPUS
 * -------------------------------------------------------------------------
 * Kontrak §4.1.2 memintanya tegas: akses bisa dipulihkan, dan riwayat kegiatan
 * member di aplikasi ini tetap harus dapat ditelusuri sesudahnya. Menghapus
 * barisnya juga akan memutus seluruh `applications` miliknya lewat foreign key.
 */
final readonly class SetMemberAccountStateAction
{
    public function __construct(
        private MemberRepositoryContract $members,
    ) {}

    public function setStatus(string $pldUserId, string $status): ProvisionResult
    {
        $member = $this->members->findByPldUserId($pldUserId);

        if ($member === null) {
            return ProvisionResult::notFound('Tidak ada akun untuk member ini.');
        }

        $this->members->update($member, ['is_active' => $status === 'ACTIVE']);

        return ProvisionResult::ok($member->user_login);
    }

    /**
     * MENGGANTI seluruh daftar peran, bukan menambah (kontrak §4.1.3): peran yang
     * tidak ikut dikirim harus dilepas. Daftar kosong karena itu sah dan berarti
     * "lepaskan semuanya" — bukan "tidak ada yang berubah".
     *
     * @param  array<int, string>  $roles  kode peran yang SUDAH divalidasi pemanggil
     */
    public function setRoles(string $pldUserId, array $roles): ProvisionResult
    {
        $member = $this->members->findByPldUserId($pldUserId);

        if ($member === null) {
            return ProvisionResult::notFound('Tidak ada akun untuk member ini.');
        }

        $this->members->update($member, ['roles' => $roles]);

        return ProvisionResult::ok($member->user_login);
    }
}
