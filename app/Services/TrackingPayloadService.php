<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\ActionRequiredData;
use App\DTOs\AttributeData;
use App\DTOs\ContactData;
use App\DTOs\DocumentData;
use App\DTOs\TimelineEntryData;
use App\DTOs\TrackingItemData;
use App\DTOs\TrackingRequestData;
use App\Enums\StageStatus;
use App\Models\Application;
use App\Models\ApplicationStage;
use App\Models\Member;
use App\Repositories\Contracts\ApplicationRepositoryContract;
use App\Repositories\Contracts\MemberRepositoryContract;
use App\Services\Contracts\TrackingPayloadServiceContract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Menyusun jawaban `API Tracking URL` — inti implementasi kontrak di sisi mitra.
 *
 * Tiga aturan kontrak yang paling sering dilanggar ditegakkan DI SINI, secara
 * konstruksi, bukan lewat validasi belakangan:
 *
 *  1. Item BERJALAN wajib punya tepat satu tahap `CURRENT`; item TERMINAL tidak
 *     boleh punya sama sekali. → status tahap DITURUNKAN dari current_stage +
 *     category (lihat stageStatusFor), tidak pernah dibaca dari basis data.
 *
 *  2. `actionRequired` wajib ada saat ACTION_REQUIRED, dan wajib TIDAK ADA selain
 *     itu. → actionRequiredFor() memeriksa kategori lebih dulu, bukan sekadar
 *     "kalau kolomnya terisi, kirim".
 *
 *  3. Tahap `DONE`/`CURRENT` wajib punya `occurredAt`. → occurredAtFor() memberi
 *     nilai cadangan yang masuk akal alih-alih membiarkan null lolos, karena satu
 *     null di sini membuat SELURUH item dibuang PLD tanpa pesan ke siapa pun.
 *
 * Melanggar salah satunya tidak menghasilkan galat yang terlihat di sisi kita —
 * PLD menolak per item dan mencatatnya di lognya sendiri. Dari kursi member,
 * permohonannya sekadar tidak pernah muncul.
 */
final readonly class TrackingPayloadService implements TrackingPayloadServiceContract
{
    public function __construct(
        private MemberRepositoryContract $members,
        private ApplicationRepositoryContract $applications,
    ) {}

    public function build(TrackingRequestData $request): array
    {
        $members = $this->members->activeByUserLogins($request->userLogins);

        if ($members->isEmpty()) {
            // Nol item BUKAN kegagalan. Kontrak §6: jawaban 200 dengan items kosong
            // dibaca PLD sebagai "member ini memang tidak punya proses apa pun", dan
            // itu pernyataan yang dipercaya. Membalas galat di sini justru salah —
            // ia membuat PLD mempertahankan cache lama selamanya.
            return [];
        }

        /** @var Collection<int, Member> $members */
        $applications = $this->applications->forMembersWithRelations(
            $members->pluck('id')->all(),
        );

        $itemsPerMember = (int) config('pld.limits.items_per_member');
        $perMemberCount = [];
        $items = [];

        foreach ($applications as $application) {
            $memberId = $application->member_id;
            $perMemberCount[$memberId] ??= 0;

            // Batas §7 ditegakkan di sisi kita juga. PLD memotong diam-diam pada
            // angka yang sama; memotong sendiri membuat mana yang terbuang jadi
            // keputusan kita (yang terbaru menang), bukan urutan acak.
            if ($perMemberCount[$memberId] >= $itemsPerMember) {
                continue;
            }

            $perMemberCount[$memberId]++;
            $items[] = $this->buildItem($application);
        }

        return $items;
    }

    public function buildItem(Application $application): TrackingItemData
    {
        return new TrackingItemData(
            userLogin: $application->member->user_login,
            externalRef: $application->external_ref,
            label: $application->label,
            category: $application->category,
            statusLabel: $application->status_label,
            submittedAt: $application->submitted_at,
            // `updatedAt` kontrak = kapan STATUS berubah. Sengaja bukan
            // $application->updated_at bawaan Eloquent: menyunting typo pada
            // statusLabel tidak boleh terbaca sebagai "prosesnya bergerak".
            updatedAt: $application->status_changed_at,
            expiresAt: $application->expires_at,
            detailUrl: $application->detailUrl(),
            currentStage: $application->current_stage,
            totalStages: $application->total_stages,
            timeline: $this->timelineFor($application),
            actionRequired: $this->actionRequiredFor($application),
            documents: $this->documentsFor($application),
            attributes: $this->attributesFor($application),
            contact: $this->contactFor($application),
        );
    }

    /**
     * @return array<int, TimelineEntryData>
     */
    private function timelineFor(Application $application): array
    {
        $maxEntries = (int) config('pld.limits.timeline_entries');

        return $application->stages
            ->take($maxEntries)
            ->map(function (ApplicationStage $stage) use ($application): TimelineEntryData {
                $status = $this->stageStatusFor($application, $stage->stage);

                return new TimelineEntryData(
                    stage: $stage->stage,
                    name: $stage->name,
                    status: $status,
                    occurredAt: $this->occurredAtFor($application, $stage, $status),
                    actor: $stage->actor,
                    note: $stage->note,
                );
            })
            ->values()
            ->all();
    }

    /**
     * Menurunkan status satu tahap. Satu-satunya sumber kebenaran untuk
     * DONE/CURRENT/PENDING di seluruh aplikasi.
     *
     * Item terminal tidak pernah menghasilkan CURRENT — tahap yang sedang berjalan
     * saat proses berakhir dihitung sebagai sudah lewat, karena memang tak ada lagi
     * yang mengerjakannya.
     */
    private function stageStatusFor(Application $application, int $stage): StageStatus
    {
        if ($application->isTerminal()) {
            return $stage <= $application->current_stage
                ? StageStatus::Done
                : StageStatus::Pending;
        }

        return match (true) {
            $stage < $application->current_stage => StageStatus::Done,
            $stage === $application->current_stage => StageStatus::Current,
            default => StageStatus::Pending,
        };
    }

    /**
     * `occurredAt` untuk tahap yang mewajibkannya.
     *
     * Nilai cadangan dipakai bila data historisnya hilang — bukan karena tanggalnya
     * penting, melainkan karena null pada tahap DONE/CURRENT membuat PLD membuang
     * SELURUH item. Kehilangan satu stempel waktu jauh lebih ringan daripada
     * kehilangan seluruh permohonan dari layar member.
     */
    private function occurredAtFor(
        Application $application,
        ApplicationStage $stage,
        StageStatus $status,
    ): ?Carbon {
        if (! $status->requiresOccurredAt()) {
            // PENDING wajib null. Mengirim tanggal untuk tahap yang belum terjadi
            // bukan pelanggaran yang ditolak PLD, tapi ia berbohong ke member.
            return null;
        }

        return $stage->occurred_at ?? $application->submitted_at;
    }

    private function actionRequiredFor(Application $application): ?ActionRequiredData
    {
        // Kategori yang menentukan, BUKAN terisinya kolom. Membaliknya berarti
        // instruksi lama yang belum dibersihkan ikut terkirim pada proses yang
        // sudah selesai — dan itu menggugurkan seluruh item.
        if (! $application->category->requiresAction()) {
            return null;
        }

        $instruction = $application->action_instruction;

        if ($instruction === null || trim($instruction) === '') {
            // Kategorinya menuntut tindakan tapi instruksinya kosong. Kontrak
            // menolak item semacam ini; diberi kalimat cadangan supaya member tetap
            // melihat permohonannya dan tahu harus menghubungi siapa.
            $instruction = 'Silakan hubungi unit terkait untuk tindak lanjut permohonan ini.';
        }

        return new ActionRequiredData(
            instruction: $instruction,
            dueAt: $application->action_due_at,
            actionUrl: $application->action_url,
        );
    }

    /**
     * @return array<int, DocumentData>
     */
    private function documentsFor(Application $application): array
    {
        return $application->documents
            ->map(static fn ($doc): DocumentData => new DocumentData(
                label: $doc->label,
                url: $doc->url,
                issuedAt: $doc->issued_at,
            ))
            ->values()
            ->all();
    }

    /**
     * @return array<int, AttributeData>
     */
    private function attributesFor(Application $application): array
    {
        $maxPairs = (int) config('pld.limits.attribute_pairs');

        return $application->attributes
            ->take($maxPairs)
            ->map(static fn ($attr): AttributeData => new AttributeData(
                label: $attr->label,
                value: $attr->value,
            ))
            ->values()
            ->all();
    }

    private function contactFor(Application $application): ?ContactData
    {
        $unit = $application->contact_unit;

        // Kontrak menolak contact yang dikirim tanpa unit. Tanpa unit, objeknya
        // memang tak punya isi yang berguna — jadi tidak dikirim sama sekali.
        if ($unit === null || trim($unit) === '') {
            return null;
        }

        return new ContactData(
            unit: $unit,
            email: $application->contact_email,
            phone: $application->contact_phone,
        );
    }
}
