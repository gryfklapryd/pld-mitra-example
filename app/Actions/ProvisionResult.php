<?php

declare(strict_types=1);

namespace App\Actions;

/**
 * Hasil satu aksi provisioning, sudah berbentuk badan jawaban kontrak §4.1.
 *
 * Dibuat sebagai objek nilai, bukan array telanjang, karena satu aturan kontrak
 * paling mudah dilanggar justru di sini: `userLogin` HANYA boleh menyertai
 * CREATED dan EXISTS_LINKED. Pada EXISTS_UNVERIFIED, mengirimkannya berarti
 * memberi tahu penanya identitas akun orang lain di sistem ini — dan larangan
 * itu jauh lebih mudah dilupakan bila jawabannya dirakit dari array di banyak
 * tempat. Di sini ia dipagari konstruktor privat: tak ada jalan menyusun
 * EXISTS_UNVERIFIED yang membawa userLogin.
 */
final readonly class ProvisionResult
{
    private function __construct(
        public string $status,
        public ?string $userLogin = null,
        public ?string $message = null,
    ) {}

    public static function created(string $userLogin): self
    {
        return new self('CREATED', $userLogin);
    }

    public static function linked(string $userLogin): self
    {
        return new self('EXISTS_LINKED', $userLogin);
    }

    public static function existsUnverified(string $message): self
    {
        return new self('EXISTS_UNVERIFIED', null, $message);
    }

    public static function roleRejected(string $message): self
    {
        return new self('ROLE_REJECTED', null, $message);
    }

    public static function ok(?string $userLogin = null): self
    {
        return new self('OK', $userLogin);
    }

    public static function notFound(string $message): self
    {
        return new self('NOT_FOUND', null, $message);
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(string $contractVersion): array
    {
        $payload = [
            'contractVersion' => $contractVersion,
            'status' => $this->status,
        ];

        if ($this->userLogin !== null) {
            $payload['userLogin'] = $this->userLogin;
        }
        if ($this->message !== null) {
            $payload['message'] = $this->message;
        }

        return $payload;
    }
}
