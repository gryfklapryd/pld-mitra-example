<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\Contracts\ApplicationRepositoryContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Halaman tujuan `detailUrl` — tempat member mendarat dari portal PLD.
 *
 * Dicari lewat `external_ref`, bukan id: itulah yang dikirim ke PLD, dan memakai
 * id di URL berarti PLD menyimpan tautan yang bergantung pada nomor baris kita.
 *
 * Halaman ini TIDAK publik meski namanya "Public" (ia rute web biasa, bukan
 * bagian API). Aksesnya dijaga ApplicationPolicy — lihat alasannya di sana.
 */
final class PublicApplicationController extends Controller
{
    public function __construct(
        private readonly ApplicationRepositoryContract $applications,
    ) {}

    public function __invoke(string $externalRef): View|RedirectResponse
    {
        $application = $this->applications->findByExternalRef($externalRef);

        if ($application === null) {
            throw new NotFoundHttpException('Permohonan tidak ditemukan.');
        }

        $member = auth()->guard('member')->user();
        $operator = auth()->guard('web')->user();

        if ($member === null && $operator === null) {
            // Member yang menekan "Buka di layanan" dari portal PLD tanpa sesi di
            // sini diarahkan ke login, lalu dikembalikan ke halaman ini. Tautan
            // dari PLD karenanya tidak pernah berakhir sebagai layar galat.
            return redirect()
                ->guest(route('masuk'))
                ->with('status', 'Masuk dengan akun PEL Anda untuk membuka permohonan ini.');
        }

        $allowed = $member !== null
            ? Gate::forUser($member)->allows('view', $application)
            : Gate::forUser($operator)->allows('viewAsOperator', $application);

        if (! $allowed) {
            // 404, bukan 403. Menjawab "ada tapi bukan milikmu" tetap membocorkan
            // keberadaan nomor permohonan itu kepada siapa pun yang menebak.
            throw new NotFoundHttpException('Permohonan tidak ditemukan.');
        }

        return view('permohonan.show', [
            'application' => $application,
            'viewedAsOperator' => $member === null,
        ]);
    }
}
