<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\HubnetUserType;
use App\Models\HubnetUser;

/**
 * Payload `/sso/api/user` — bentuk yang dibaca pld-user (HubnetDataUser).
 *
 * Serialisasinya sengaja terkurung di SATU tempat, karena bentuk inilah kontrak
 * sesungguhnya antara tiruan ini dan pld-user. Dua hal yang gampang salah dan
 * karenanya dijaga di sini:
 *
 *   - `type` harus int (1/2/3), bukan string. pld-user membacanya sebagai `int`.
 *   - `status`/`is_deleted` harus ANGKA (1/0). pld-user membacanya sebagai
 *     json.RawMessage dan hanya mengenali "1"/"0"/"true"/"false"; bentuk lain
 *     dianggap "tidak ada sinyal" dan DILOLOSKAN — diam-diam melewati cek B5.
 *
 * Payload TIDAK seragam antar tipe, meniru Hubnet asli: type 1 memuat atribut
 * pegawai lengkap; type 2 hanya identitas usaha tanpa penanda status; type 3
 * paling ramping (dan ditolak pld-user pada tahap `type`).
 */
final readonly class HubnetUserInfoData
{
    public function __construct(
        private HubnetUser $user,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return match ($this->user->type) {
            HubnetUserType::Pegawai => $this->pegawai(),
            HubnetUserType::Oss => $this->oss(),
            HubnetUserType::Lainnya => $this->lainnya(),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function pegawai(): array
    {
        return [
            'type' => HubnetUserType::Pegawai->value,
            'username' => $this->user->username,
            'name' => $this->user->name,
            'nip' => $this->user->nip,
            'nik' => $this->user->nik,
            'email' => $this->user->email,
            'telepon' => $this->user->phone,
            'kode_unit' => $this->user->kode_unit,
            'unit' => $this->user->unit,
            'golongan' => $this->user->golongan,
            'pangkat' => $this->user->pangkat,
            'jabatan_struktural' => $this->user->jabatan_struktural,
            'jabatan_fungsional' => $this->user->jabatan_fungsional,
            'status' => $this->flag($this->user->status),
            'is_deleted' => $this->flag($this->user->is_deleted),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function oss(): array
    {
        // Type 2 tidak membawa penanda status sama sekali (poin 17): PLD bergantung
        // pada Hubnet menolak badan usaha yang dicabut sebelum sampai ke sini.
        return [
            'type' => HubnetUserType::Oss->value,
            'username' => $this->user->username,
            'name' => $this->user->name,
            'nip' => null,
            'nik' => $this->user->nik,
            'npwp' => $this->user->npwp,
            'email' => $this->user->email,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function lainnya(): array
    {
        return [
            'type' => HubnetUserType::Lainnya->value,
            'username' => $this->user->username,
            'name' => $this->user->name,
            'nip' => null,
            'nik' => $this->user->nik,
            'email' => $this->user->email,
        ];
    }

    private function flag(bool $value): int
    {
        return $value ? 1 : 0;
    }
}
