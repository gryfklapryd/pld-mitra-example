<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DTOs\PublishNotificationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PublishNotificationRequest;
use App\Repositories\Contracts\MemberRepositoryContract;
use App\Services\Contracts\PldNotificationClientContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Uji Jalur B — mengirim pengumuman bebas ke PLD.
 *
 * Sengaja dipisahkan dari layar permohonan supaya batas antara dua jalur tetap
 * terlihat: perubahan status permohonan TIDAK dikirim lewat sini. Jalur A sudah
 * menerbitkan notifikasinya sendiri dari perubahan jawaban tracking, dan memakai
 * keduanya untuk peristiwa yang sama menghasilkan notifikasi ganda — pembagian
 * itu belum dijaga PLD, jadi ia tanggung jawab kita.
 */
final class PublishController extends Controller
{
    public function __construct(
        private readonly MemberRepositoryContract $members,
        private readonly PldNotificationClientContract $client,
    ) {}

    public function create(): View
    {
        return view('admin.publish.create', [
            'members' => $this->members->all(),
            'configured' => $this->client->isConfigured(),
        ]);
    }

    public function store(PublishNotificationRequest $request): RedirectResponse
    {
        $result = $this->client->publish(PublishNotificationData::toEmail(
            email: (string) $request->validated('email'),
            eventType: (string) $request->validated('event_type'),
            title: (string) $request->validated('title'),
            message: (string) $request->validated('message'),
            actionLabel: $request->validated('action_label') ?: null,
            actionPath: $request->validated('action_path') ?: null,
        ));

        if (! $result->accepted) {
            return back()
                ->withInput()
                ->with('error', $result->message.($result->isRetryable() ? ' (layak dicoba ulang)' : ' (jangan diulang — perbaiki dulu)'));
        }

        return back()->with(
            'status',
            'PLD menjawab 202 ACCEPTED — diterima dan dijadwalkan (bukan berarti email sudah sampai). Kanal: '
            .implode(', ', $result->channels),
        );
    }
}
