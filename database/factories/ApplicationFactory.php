<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TrackingCategory;
use App\Models\Application;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
final class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $submittedAt = $this->faker->dateTimeBetween('-6 months', '-1 month');

        return [
            'external_ref' => sprintf('PRZ-%s-%05d', now()->format('Y'), $this->faker->unique()->numberBetween(1, 99999)),
            'member_id' => Member::factory(),
            'label' => 'Permohonan '.$this->faker->words(3, true),
            'category' => TrackingCategory::InProgress,
            'status_label' => 'Menunggu verifikasi berkas',
            'submitted_at' => $submittedAt,
            'status_changed_at' => $submittedAt,
            'current_stage' => 1,
            'total_stages' => 5,
        ];
    }

    /**
     * Membuat tahap sekaligus, karena `timeline` wajib berisi minimal satu entri
     * dan tahap DONE/CURRENT wajib punya `occurred_at`. Application tanpa tahap
     * menghasilkan item yang ditolak PLD.
     */
    public function withStages(int $total = 5, int $currentStage = 1): self
    {
        return $this->state(fn (): array => [
            'total_stages' => $total,
            'current_stage' => $currentStage,
        ])->afterCreating(function (Application $application) use ($total, $currentStage): void {
            for ($stage = 1; $stage <= $total; $stage++) {
                $application->stages()->create([
                    'stage' => $stage,
                    'name' => "Tahap {$stage}",
                    'occurred_at' => $stage <= $currentStage ? $application->submitted_at : null,
                ]);
            }
        });
    }

    public function actionRequired(string $instruction = 'Lengkapi dokumen yang diminta.'): self
    {
        return $this->state(fn (): array => [
            'category' => TrackingCategory::ActionRequired,
            'status_label' => 'Menunggu tindakan pemohon',
            'action_instruction' => $instruction,
        ]);
    }

    public function terminal(TrackingCategory $category = TrackingCategory::Completed): self
    {
        return $this->state(fn (): array => [
            'category' => $category,
            'status_label' => $category->label(),
            // Instruksi WAJIB kosong pada kategori non-ACTION_REQUIRED.
            'action_instruction' => null,
            'action_due_at' => null,
            'action_url' => null,
        ]);
    }
}
