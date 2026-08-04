<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\ActionRequiredData;
use App\Enums\TrackingCategory;
use App\Models\Application;
use Illuminate\Database\ConnectionInterface;
use InvalidArgumentException;

/**
 * Mengubah kategori proses — pemicu notifikasi paling penting di sisi PLD.
 *
 * Perpindahan kategori inilah yang menerbitkan EMAIL ke member (kontrak §6):
 * ACTION_REQUIRED, COMPLETED, dan keempat kategori terminal negatif. Kenaikan
 * tahap biasa hanya muncul di lonceng portal.
 *
 * Action ini memaksa pasangan kategori↔actionRequired tetap sah dalam satu
 * operasi. Membiarkan keduanya diubah terpisah adalah cara paling mudah
 * menghasilkan item yang ditolak PLD: kategori sudah COMPLETED tetapi instruksi
 * lama belum dibersihkan.
 */
final readonly class ChangeApplicationCategoryAction
{
    public function __construct(
        private ConnectionInterface $connection,
    ) {}

    public function __invoke(
        Application $application,
        TrackingCategory $category,
        string $statusLabel,
        ?ActionRequiredData $actionRequired = null,
        ?string $note = null,
    ): Application {
        if ($category->requiresAction() && $actionRequired === null) {
            throw new InvalidArgumentException(
                'Kategori ACTION_REQUIRED wajib disertai instruksi tindakan.',
            );
        }

        if (! $category->requiresAction() && $actionRequired !== null) {
            // Ditolak, bukan diabaikan diam-diam. Kalau dibiarkan lolos, instruksi
            // itu akan tetap tersimpan lalu ikut terkirim, dan PLD membuang seluruh
            // item — kegagalan yang muncul jauh dari penyebabnya.
            throw new InvalidArgumentException(
                'Instruksi tindakan hanya boleh untuk kategori ACTION_REQUIRED.',
            );
        }

        return $this->connection->transaction(function () use (
            $application,
            $category,
            $statusLabel,
            $actionRequired,
            $note,
        ): Application {
            // Alasan penolakan/pencabutan disampaikan lewat note pada tahap tertinggi
            // yang sudah dilalui — kontrak §6 mengambilnya dari sana untuk isi email.
            // Tanpa itu member menerima kabar "ditolak" tanpa sebab.
            if ($note !== null && trim($note) !== '') {
                $application->stages()
                    ->where('stage', $application->current_stage)
                    ->update(['note' => $note, 'updated_at' => now()]);
            }

            $application->update([
                'category' => $category,
                'status_label' => $statusLabel,
                'status_changed_at' => now(),
                'action_instruction' => $actionRequired?->instruction,
                'action_due_at' => $actionRequired?->dueAt,
                'action_url' => $actionRequired?->actionUrl,
            ]);

            return $application->refresh();
        });
    }
}
