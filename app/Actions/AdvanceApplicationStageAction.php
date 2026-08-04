<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TrackingCategory;
use App\Models\Application;
use Illuminate\Database\ConnectionInterface;
use RuntimeException;

/**
 * Menaikkan proses satu tahap.
 *
 * Di sisi PLD, kenaikan `currentStage` menerbitkan notifikasi in-app (tanpa email)
 * yang memuat nama tahap dari `timeline[]` — jadi nama tahap di sini benar-benar
 * dibaca member, bukan sekadar label internal.
 *
 * Proses yang sudah terminal tidak bisa dinaikkan: menaikkannya akan menghasilkan
 * item dengan tahap CURRENT pada kategori terminal, yang ditolak PLD seluruhnya.
 */
final readonly class AdvanceApplicationStageAction
{
    public function __construct(
        private ConnectionInterface $connection,
    ) {}

    public function __invoke(
        Application $application,
        ?string $note = null,
        ?string $actor = null,
    ): Application {
        if ($application->isTerminal()) {
            throw new RuntimeException(
                'Proses yang sudah berakhir tidak dapat dinaikkan tahapnya.',
            );
        }

        if ($application->current_stage >= $application->total_stages) {
            throw new RuntimeException(
                'Sudah berada di tahap terakhir. Selesaikan atau tolak prosesnya.',
            );
        }

        return $this->connection->transaction(function () use ($application, $note, $actor): Application {
            $next = $application->current_stage + 1;

            // Tahap yang baru dicapai WAJIB punya occurredAt — ia akan berstatus
            // CURRENT pada payload berikutnya, dan CURRENT tanpa occurredAt membuat
            // seluruh item dibuang PLD.
            $application->stages()
                ->where('stage', $next)
                ->update([
                    'occurred_at' => now(),
                    'note' => $note,
                    'actor' => $actor,
                    'updated_at' => now(),
                ]);

            $stageName = $application->stages()
                ->where('stage', $next)
                ->value('name');

            $application->update([
                'current_stage' => $next,
                'status_label' => $stageName ?? "Tahap {$next}",
                'status_changed_at' => now(),
                // Naik tahap berarti bola kembali ke pihak kita. Instruksi tindakan
                // yang tersisa dari tahap sebelumnya harus dibersihkan — kontrak
                // menolak item non-ACTION_REQUIRED yang masih membawa actionRequired.
                'category' => TrackingCategory::InProgress,
                'action_instruction' => null,
                'action_due_at' => null,
                'action_url' => null,
            ]);

            return $application->refresh();
        });
    }
}
