<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Muatan Jalur B — yang aplikasi ini KIRIM ke `POST /notif/publish` milik PLD.
 *
 * Tiga hal sengaja tidak ada di sini, dan bukan karena terlupa:
 *
 *   - alamat email tujuan — PLD mengambilnya dari data member;
 *   - asal layanan — PLD menyimpulkannya dari Service Key kita;
 *   - host tombol aksi — PLD menyusunnya dari Redirect URL terdaftar.
 *
 * Karena itu `actionPath` adalah PATH RELATIF. Mengirim URL lengkap akan ditolak
 * 400 oleh PLD, dan itu memang perilaku yang diinginkan: kalau host boleh datang
 * dari kita, email berkop AviasiHub bisa menaut ke domain mana pun.
 */
final readonly class PublishNotificationData
{
    /**
     * @param  array<int, array{label: string, value: string}>  $details
     * @param  array<int, string>  $channels
     */
    private function __construct(
        public ?string $recipientUserId,
        public ?string $recipientEmail,
        public string $eventType,
        public string $title,
        public string $message,
        public array $details,
        public ?string $actionLabel,
        public ?string $actionPath,
        public array $channels,
    ) {}

    /**
     * @param  array<int, array{label: string, value: string}>  $details
     * @param  array<int, string>  $channels
     */
    public static function toEmail(
        string $email,
        string $eventType,
        string $title,
        string $message,
        array $details = [],
        ?string $actionLabel = null,
        ?string $actionPath = null,
        array $channels = ['EMAIL', 'INAPP'],
    ): self {
        return new self(
            recipientUserId: null,
            recipientEmail: $email,
            eventType: $eventType,
            title: $title,
            message: $message,
            details: $details,
            actionLabel: $actionLabel,
            actionPath: $actionPath,
            channels: $channels,
        );
    }

    /**
     * @param  array<int, array{label: string, value: string}>  $details
     * @param  array<int, string>  $channels
     */
    public static function toUserId(
        string $userId,
        string $eventType,
        string $title,
        string $message,
        array $details = [],
        ?string $actionLabel = null,
        ?string $actionPath = null,
        array $channels = ['EMAIL', 'INAPP'],
    ): self {
        return new self(
            recipientUserId: $userId,
            recipientEmail: null,
            eventType: $eventType,
            title: $title,
            message: $message,
            details: $details,
            actionLabel: $actionLabel,
            actionPath: $actionPath,
            channels: $channels,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toRequestArray(): array
    {
        // Tepat satu dari userId/email — dua-duanya terisi dijawab 400 oleh PLD.
        // Konstruktor privat + dua named constructor di atas membuat keadaan itu
        // tidak bisa dibentuk sejak awal.
        $recipient = $this->recipientUserId !== null
            ? ['userId' => $this->recipientUserId]
            : ['email' => $this->recipientEmail];

        return array_filter([
            'recipient' => $recipient,
            'eventType' => $this->eventType,
            'title' => $this->title,
            'message' => $this->message,
            'details' => $this->details !== [] ? $this->details : null,
            'actionLabel' => $this->actionLabel,
            'actionPath' => $this->actionPath,
            'channels' => $this->channels,
        ], static fn (mixed $v): bool => $v !== null);
    }
}
