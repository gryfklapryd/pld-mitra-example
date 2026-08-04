<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\Contracts\ApplicationRepositoryContract;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Halaman tujuan `detailUrl` — tempat member mendarat dari portal PLD.
 *
 * Dicari lewat `external_ref`, bukan id: itulah yang dikirim ke PLD, dan memakai
 * id di URL berarti PLD menyimpan tautan yang bergantung pada nomor baris kita.
 */
final class PublicApplicationController extends Controller
{
    public function __construct(
        private readonly ApplicationRepositoryContract $applications,
    ) {}

    public function __invoke(string $externalRef): View
    {
        $application = $this->applications->findByExternalRef($externalRef);

        if ($application === null) {
            throw new NotFoundHttpException('Permohonan tidak ditemukan.');
        }

        return view('permohonan.show', [
            'application' => $application,
        ]);
    }
}
