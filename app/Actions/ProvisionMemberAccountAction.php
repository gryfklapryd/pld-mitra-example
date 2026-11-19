<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Member;
use App\Repositories\Contracts\MemberRepositoryContract;
use Illuminate\Support\Str;

/**
 * Aksi `createAccount` — membuatkan akun member atas permintaan PLD.
 *
 * Mengembalikan salah satu dari empat keadaan kontrak §4.1.1, dan pemilihannya
 * adalah inti keamanan endpoint ini:
 *
 *   CREATED            akun baru dibuat
 *   EXISTS_LINKED      akun sudah ada DAN kepemilikan identitasnya pernah kita
 *                      verifikasi sendiri
 *   EXISTS_UNVERIFIED  akun sudah ada tapi TIDAK PERNAH kita verifikasi
 *   ROLE_REJECTED      ada kode peran yang tak kita kenal
 *
 * -------------------------------------------------------------------------
 * KENAPA `EXISTS_UNVERIFIED` BUKAN FORMALITAS
 * -------------------------------------------------------------------------
 * Aplikasi ini — seperti kebanyakan aplikasi lama — pernah membiarkan orang
 * mendaftar dengan email yang tak pernah dibuktikan kepemilikannya. Untuk akun
 * seperti itu, kecocokan email BUKAN bukti bahwa orangnya sama. Menjawab
 * EXISTS_LINKED di situ berarti menyerahkan akun ini kepada siapa pun yang
 * mendaftar di PLD memakai email yang sama; kontrak §4.1.1 menyebutnya
 * terus terang sebagai jalur pengambilalihan akun.
 *
 * Jawaban EXISTS_UNVERIFIED tidak merugikan siapa pun: PLD hanya meminta member
 * membuktikan kepemilikannya sekali lewat "Tautkan Akun", memakai password
 * aplikasi ini — jalur yang sudah berjalan lewat `API User Validation URL`.
 *
 * Dan pada keadaan itu `userLogin` TIDAK dikembalikan. Mengembalikannya berarti
 * memberi tahu penanya identitas akun orang lain di sistem ini.
 *
 * -------------------------------------------------------------------------
 * KENAPA AKUN LAHIR TANPA PASSWORD YANG BISA DIPAKAI
 * -------------------------------------------------------------------------
 * Kontrak §8 melarang password mengalir lewat PLD, dan melarang mengembalikannya
 * di dalam jawaban. Akun karena itu diberi password acak yang tak pernah
 * diberitahukan kepada siapa pun: masuknya lewat SSO PLD. Bila kelak member
 * ingin masuk langsung ke aplikasi ini, ia menempuh alur "lupa password" milik
 * aplikasi ini sendiri — bukan meminta PLD menyampaikannya.
 */
final readonly class ProvisionMemberAccountAction
{
    public function __construct(
        private MemberRepositoryContract $members,
    ) {}

    /**
     * @param  array<string, mixed>  $subject
     * @param  array<int, string>  $roles  kode peran yang SUDAH divalidasi pemanggil
     * @param  array<int, array{code: string, roles: array<int, string>}>|null  $scopes  null = kunci scopes tidak dikirim
     */
    public function __invoke(array $subject, array $roles, ?array $scopes = null): ProvisionResult
    {
        $lingkup = $scopes === null ? [] : ['scopes' => $scopes];

        $pldUserId = (string) $subject['pldUserId'];
        $email = (string) $subject['email'];
        $nip = isset($subject['nip']) ? (string) $subject['nip'] : null;

        // Urutan pencocokan mengikuti kontrak §10: pldUserId lebih dulu.
        // Ia satu-satunya penanda yang tak berubah saat member mengganti email
        // di PLD — mencocokkan lewat email lebih dulu akan menganggapnya orang
        // baru lalu membuatkan akun kedua untuk orang yang sama.
        $member = $this->members->findByPldUserId($pldUserId);

        if ($member !== null) {
            // Sudah pernah ditautkan lewat jalur ini: kepemilikannya sudah pasti,
            // karena tautannya sendiri yang membuktikannya.
            $this->members->update($member, [
                'roles' => $roles,
                'is_active' => true,
            ] + $lingkup);

            return ProvisionResult::linked($member->user_login);
        }

        // Belum tertaut. Cari akun lama yang identitasnya cocok — inilah kasus
        // yang paling sering, karena sebagian besar member sudah punya akun di
        // sini jauh sebelum PLD mengenal mereka.
        $member = $this->members->findByIdentity($email, $nip);

        if ($member !== null) {
            if ($member->email_verified_at === null) {
                // Kita tak pernah membuktikan email ini miliknya. Berhenti di sini.
                return ProvisionResult::existsUnverified(
                    'Akun dengan identitas tersebut sudah terdaftar, tetapi aplikasi ini belum pernah memverifikasi kepemilikannya.',
                );
            }

            $this->members->update($member, [
                'pld_user_id' => $pldUserId,
                'roles' => $roles,
                'is_active' => true,
            ] + $lingkup);

            return ProvisionResult::linked($member->user_login);
        }

        // Benar-benar orang baru bagi aplikasi ini.
        //
        // user_login memakai NIP untuk pegawai dan email untuk selainnya, persis
        // seperti yang disarankan kontrak §5: PLD tidak memaksakan formatnya dan
        // justru menunggu kita memberitahukan hasilnya lewat `userLogin`.
        $userLogin = $nip !== null && $nip !== '' ? $nip : $email;

        $member = $this->members->create([
            'pld_user_id' => $pldUserId,
            'user_login' => $userLogin,
            'name' => (string) $subject['fullName'],
            'email' => $email,
            // Terverifikasi karena PLD-lah yang memverifikasinya saat registrasi,
            // dan akun ini lahir DARI permintaan PLD. Berbeda dari akun lama yang
            // asal-usulnya tak kita ketahui.
            'email_verified_at' => now(),
            'password' => Str::password(32),
            'is_active' => true,
            'roles' => $roles,
            'provisioned_at' => now(),
        ] + $lingkup);

        return ProvisionResult::created($member->user_login);
    }

    /**
     * Menyaring kode peran terhadap daftar yang dikenal aplikasi ini.
     *
     * Kontrak §8: `roles[]` adalah PERMINTAAN yang harus divalidasi, bukan
     * perintah. Mengembalikan null bila ada kode yang tak dikenal — pemanggil
     * menjawabnya ROLE_REJECTED alih-alih diam-diam menyimpan peran yang tak
     * berarti apa-apa di sini.
     *
     * @param  array<int, string>  $requested
     * @param  array<int, string>  $known
     * @return array<int, string>|null
     */
    public static function sanitizeRoles(array $requested, array $known, ?string $default): ?array
    {
        $normalizedKnown = array_map('mb_strtoupper', $known);
        $out = [];

        foreach ($requested as $role) {
            $role = mb_strtoupper(trim((string) $role));

            if ($role === '') {
                continue;
            }
            if (! in_array($role, $normalizedKnown, true)) {
                return null;
            }
            if (! in_array($role, $out, true)) {
                $out[] = $role;
            }
        }

        // PLD tidak menyebut peran sama sekali: pakai peran bawaan aplikasi ini.
        // Menolak permintaannya hanya memindahkan kebuntuan ke member, yang tak
        // bisa berbuat apa-apa soal konfigurasi katalog peran di seberang.
        if ($out === [] && $default !== null && $default !== '') {
            $out[] = mb_strtoupper($default);
        }

        return $out;
    }

    /**
     * Kode lingkup pada `scopes` yang tidak dikenal aplikasi ini, dibandingkan persis. Kode asing
     * dijawab SCOPE_REJECTED (bagian 4.1.7) supaya pengelola layanan menyamakan kode di PLD,
     * bukan diam-diam disimpan sebagai keanggotaan yang tidak berarti apa-apa di sini.
     *
     * @param  array<int, array{code: string, roles: array<int, string>}>  $scopes
     * @param  array<int, string>  $known
     * @return array<int, string>
     */
    public static function unknownScopeCodes(array $scopes, array $known): array
    {
        $asing = [];
        foreach ($scopes as $scope) {
            $kode = trim((string) ($scope['code'] ?? ''));
            if (! in_array($kode, $known, true) && ! in_array($kode, $asing, true)) {
                $asing[] = $kode;
            }
        }

        return $asing;
    }

    /**
     * Bentuk simpan `scopes`: kode apa adanya, kode peran huruf besar, unik, dan terurut.
     *
     * @param  array<int, array{code: string, roles: array<int, string>}>  $scopes
     * @return array<int, array{code: string, roles: array<int, string>}>
     */
    public static function normalizeScopes(array $scopes): array
    {
        $out = [];
        foreach ($scopes as $scope) {
            $peran = array_values(array_unique(array_map(
                static fn ($r): string => mb_strtoupper(trim((string) $r)),
                (array) ($scope['roles'] ?? []),
            )));
            sort($peran);
            $out[] = ['code' => trim((string) $scope['code']), 'roles' => $peran];
        }
        usort($out, static fn (array $a, array $b): int => strcmp($a['code'], $b['code']));

        return $out;
    }
}
