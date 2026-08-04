<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repositories\Contracts\MemberRepositoryContract;
use Illuminate\Contracts\Hashing\Hasher;

/**
 * Memeriksa pasangan `user_login` + `password` untuk `API User Validation URL`.
 *
 * Dipakai PLD saat member menautkan akun aplikasi ini ke akun PLD-nya. Perlu
 * disadari betul: **password member benar-benar dikirim PLD ke sini**, jadi
 * endpoint yang memanggil action ini wajib HTTPS di produksi, dan payload-nya
 * tidak boleh masuk log apa adanya (lihat App\Support\PayloadRedactor).
 *
 * Kontrak membedakan dua kegagalan, dan bedanya penting:
 *   - user_login tak dikenal → 400 (PLD menghitungnya sebagai percobaan gagal)
 *   - password salah         → 200 {"is_valid": false}
 *
 * Keduanya sengaja TIDAK dibedakan dalam pesan ke pemanggil di luar itu: selisih
 * jawaban yang lebih halus menjadikan endpoint ini alat memeriksa "apakah
 * username X terdaftar di aplikasi ini".
 */
final readonly class ValidateMemberCredentialsAction
{
    /**
     * Hash bcrypt yang SAH atas nilai sembarang, dipakai hanya sebagai pengimbang
     * waktu saat member tidak ditemukan.
     *
     * Harus hash bcrypt sungguhan: `Hasher::check()` memeriksa algoritmanya lebih
     * dulu dan MELEMPAR RuntimeException untuk string yang bukan bcrypt — yang
     * berarti jalur "user tak dikenal" berubah dari 400 menjadi 500, dan di sisi
     * PLD 500 tidak dihitung sebagai percobaan gagal (lihat UserValidationController).
     */
    private const TIMING_GUARD_HASH = '$2y$12$0LEKn22TsHJvhXQym31bzOE9h6VwCt9M34pg8R4L9wVmUIgqqYa.a';

    public function __construct(
        private MemberRepositoryContract $members,
        private Hasher $hasher,
    ) {}

    public function __invoke(string $userLogin, string $password): ValidationOutcome
    {
        $member = $this->members->findByUserLogin($userLogin);

        if ($member === null) {
            // Hash dummy tetap dihitung supaya waktu jawab untuk "user tak ada"
            // tidak jauh lebih cepat daripada "password salah". Tanpa ini, selisih
            // waktunya sendiri sudah cukup untuk menyaring daftar username.
            $this->hasher->check($password, self::TIMING_GUARD_HASH);

            return ValidationOutcome::NotFound;
        }

        if (! $member->is_active) {
            // Akun nonaktif diperlakukan sebagai kredensial tidak sah, bukan 400:
            // ia memang ada, hanya tak boleh dipakai menautkan apa pun.
            return ValidationOutcome::Invalid;
        }

        return $this->hasher->check($password, $member->password)
            ? ValidationOutcome::Valid
            : ValidationOutcome::Invalid;
    }
}
