<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IntegrationLog;
use App\Repositories\Contracts\IntegrationLogRepositoryContract;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Layar "apakah PLD sudah memanggil kita?".
 *
 * Pertanyaan pertama pada setiap kegagalan integrasi, dan tanpa layar ini
 * jawabannya hanya bisa ditebak dari log dua server yang tak pernah dibaca
 * bersamaan.
 */
final class IntegrationLogController extends Controller
{
    public function __construct(
        private readonly IntegrationLogRepositoryContract $logs,
    ) {}

    public function index(Request $request): View
    {
        $direction = $request->string('direction')->value() ?: null;

        if ($direction !== null && ! in_array($direction, [
            IntegrationLog::DIRECTION_INBOUND,
            IntegrationLog::DIRECTION_OUTBOUND,
        ], true)) {
            $direction = null;
        }

        return view('admin.logs.index', [
            'logs' => $this->logs->paginate($direction),
            'direction' => $direction,
            'lastTrackingAt' => $this->logs->lastInboundAt('API Tracking URL'),
        ]);
    }
}
