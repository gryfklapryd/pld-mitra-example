<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Klien OAuth yang terdaftar di tiruan Hubnet (config/hubnet.php).
 *
 * Value object tak berubah: identitas klien + rahasianya + daftar redirect_uri
 * yang diizinkan. Verifikasinya di sini, di satu tempat, supaya "cocok atau
 * tidak" tak pernah ditafsirkan berbeda oleh dua pemanggil.
 */
final readonly class OAuthClient
{
    /**
     * @param  array<int, string>  $redirectUris
     */
    public function __construct(
        public string $id,
        private string $secret,
        private array $redirectUris,
    ) {}

    /**
     * Perbandingan rahasia dengan hash_equals: klien ini dipanggil mesin yang
     * boleh mencoba berulang, persis keadaan yang membuat serangan waktu praktis.
     */
    public function secretMatches(string $provided): bool
    {
        return $provided !== '' && hash_equals($this->secret, $provided);
    }

    /**
     * Pencocokan redirect_uri SAMA PERSIS, bukan awalan. Longgar di sini =
     * pencurian authorization code lewat pengalihan.
     */
    public function allowsRedirectUri(string $uri): bool
    {
        foreach ($this->redirectUris as $allowed) {
            if (hash_equals($allowed, $uri)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Satu redirect_uri untuk ditawarkan saat authorize tidak menyertakannya.
     * Berguna hanya untuk kenyamanan uji manual; alur PLD selalu mengirimkannya.
     */
    public function defaultRedirectUri(): ?string
    {
        return $this->redirectUris[0] ?? null;
    }
}
