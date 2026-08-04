<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TrackingCategory;
use App\Models\Application;
use App\Models\Member;
use App\Repositories\Contracts\ApplicationRepositoryContract;
use Illuminate\Database\ConnectionInterface;

/**
 * Membuat proses baru beserta seluruh tahapnya.
 *
 * Tahap dibuat SEKALIGUS di depan, termasuk yang masih PENDING. Kontrak
 * menganjurkannya (§10 checklist) dengan alasan yang sepenuhnya berpihak pada
 * member: perjalanan yang hanya menampilkan tahap yang sudah lewat tidak memberi
 * tahu apa yang menunggu di depan, dan "masih ada berapa lagi" justru pertanyaan
 * yang membuat orang menelepon.
 */
final readonly class CreateApplicationAction
{
    public function __construct(
        private ApplicationRepositoryContract $applications,
        private ConnectionInterface $connection,
    ) {}

    /**
     * @param  array<int, string>  $stageNames
     * @param  array<int, array{label: string, value: string}>  $attributes
     */
    public function __invoke(
        Member $member,
        string $label,
        array $stageNames,
        ?string $contactUnit = null,
        ?string $contactEmail = null,
        ?string $contactPhone = null,
        array $attributes = [],
    ): Application {
        return $this->connection->transaction(function () use (
            $member,
            $label,
            $stageNames,
            $contactUnit,
            $contactEmail,
            $contactPhone,
            $attributes,
        ): Application {
            $now = now();

            $application = Application::query()->create([
                'external_ref' => $this->nextExternalRef(),
                'member_id' => $member->id,
                'label' => $label,
                'category' => TrackingCategory::InProgress,
                'status_label' => $stageNames[0],
                'submitted_at' => $now,
                'status_changed_at' => $now,
                'current_stage' => 1,
                'total_stages' => count($stageNames),
                'contact_unit' => $contactUnit,
                'contact_email' => $contactEmail,
                'contact_phone' => $contactPhone,
            ]);

            foreach ($stageNames as $index => $name) {
                $stage = $index + 1;

                $application->stages()->create([
                    'stage' => $stage,
                    'name' => $name,
                    // Hanya tahap pertama yang sudah terjadi; sisanya menunggu.
                    'occurred_at' => $stage === 1 ? $now : null,
                    'actor' => $stage === 1 ? 'Sistem' : null,
                ]);
            }

            foreach ($attributes as $attribute) {
                $application->attributes()->create($attribute);
            }

            return $application->refresh();
        });
    }

    /**
     * `external_ref` yang stabil sepanjang hidup proses — kunci pembaruan di PLD.
     */
    private function nextExternalRef(): string
    {
        $year = (int) now()->format('Y');
        $sequence = $this->applications->countForYear($year) + 1;

        return sprintf('PRZ-%d-%05d', $year, $sequence);
    }
}
