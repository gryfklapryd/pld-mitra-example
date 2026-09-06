<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\ProvisionMemberAccountAction;
use App\Actions\ProvisionResult;
use App\Actions\SetMemberAccountStateAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ProvisioningRequest;
use App\Repositories\Contracts\ProvisioningRequestStoreContract;
use App\Services\Contracts\IntegrationLoggerContract;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * `API Provisioning URL` — PLD meminta aplikasi ini membuatkan, menonaktifkan,
 * atau mengganti peran akun seorang member (kontrak provisioning v1.0).
 *
 * Satu alamat, tiga aksi, dibedakan `action` pada badan. Kontrak §4.1 memilih
 * bentuk itu supaya pendaftaran layanan di PLD tidak bertambah tiga kolom.
 *
 * -------------------------------------------------------------------------
 * JAWABAN SELALU 200 SELAMA PERMINTAANNYA SAH
 * -------------------------------------------------------------------------
 * Hasilnya dibedakan lewat field `status` pada badan, bukan lewat kode HTTP —
 * bentuk yang sama dengan `is_valid` pada `API User Validation URL`. Empat
 * keadaan createAccount (CREATED / EXISTS_LINKED / EXISTS_UNVERIFIED /
 * ROLE_REJECTED) menuntut empat tindakan berbeda di sisi PLD, dan tak satu pun
 * bisa disimpulkan dari kode status HTTP saja.
 *
 * Yang memakai kode HTTP hanyalah nasib PERMINTAANNYA: 400 untuk permintaan cacat
 * MAUPUN kunci yang tidak sah (keduanya di middleware/FormRequest), 500 gangguan
 * kita. 400 untuk kunci salah mengikuti tiga endpoint kontrak yang lebih tua —
 * middleware-nya sama, dan memaksakan 401 khusus di sini hanya membuat mitra
 * menangani dua bentuk penolakan untuk satu gerbang yang sama.
 * Pembedaan itu bukan gaya-gayaan — di sisi PLD, 400/401 berarti "berhenti,
 * jangan diulang" sedangkan 5xx berarti "ulangi nanti".
 *
 * -------------------------------------------------------------------------
 * IDEMPOTENSI DIPERIKSA PALING AWAL
 * -------------------------------------------------------------------------
 * Sebelum satu baris pun disentuh. Kontrak §7: percobaan ulang membawa
 * `requestId` yang sama persis, dan wajib dijawab sama tanpa dikerjakan lagi.
 * Yang dijaga adalah akun ganda — jawaban 200 yang hilang di jaringan membuat
 * PLD mengulang, dan tanpa ingatan ini percobaan kedua membuat akun kedua untuk
 * orang yang sama.
 */
final class ProvisioningController extends Controller
{
    public function __construct(
        private readonly ProvisionMemberAccountAction $provisionAccount,
        private readonly SetMemberAccountStateAction $accountState,
        private readonly ProvisioningRequestStoreContract $requests,
        private readonly IntegrationLoggerContract $logger,
    ) {}

    public function __invoke(ProvisioningRequest $request): JsonResponse
    {
        $startedAt = microtime(true);

        /** @var array<string, mixed> $validated */
        $validated = $request->validated();

        $requestId = (string) $validated['requestId'];
        $action = (string) $validated['action'];
        /** @var array<string, mixed> $subject */
        $subject = $validated['subject'];
        $pldUserId = (string) $subject['pldUserId'];

        // Sudah pernah dijawab? Kembalikan jawaban yang ITU JUGA.
        $sebelumnya = $this->requests->find($requestId);

        if ($sebelumnya !== null) {
            $this->log($request, $sebelumnya['response'], $sebelumnya['status'], $startedAt, 'diulang');

            return new JsonResponse($sebelumnya['response'], $sebelumnya['status']);
        }

        $contractVersion = (string) config('pld.contract_version', '1.0');
        $result = $this->jalankan($action, $subject, $validated);

        $payload = $result->toPayload($contractVersion);
        $tersimpan = $this->requests->remember($requestId, $action, $pldUserId, $payload, Response::HTTP_OK);

        $this->log($request, $tersimpan['response'], $tersimpan['status'], $startedAt, $action);

        return new JsonResponse($tersimpan['response'], $tersimpan['status']);
    }

    /**
     * @param  array<string, mixed>  $subject
     * @param  array<string, mixed>  $validated
     */
    private function jalankan(string $action, array $subject, array $validated): ProvisionResult
    {
        $pldUserId = (string) $subject['pldUserId'];

        if ($action === 'setStatus') {
            return $this->accountState->setStatus($pldUserId, (string) $validated['status']);
        }

        /** @var array<int, string> $diminta */
        $diminta = $validated['roles'] ?? [];

        $peran = ProvisionMemberAccountAction::sanitizeRoles(
            $diminta,
            (array) config('pld.provisioning.roles', []),
            $action === 'createAccount' ? (string) config('pld.provisioning.default_role') : null,
        );

        if ($peran === null) {
            return ProvisionResult::roleRejected(
                'Ada kode peran yang tidak dikenal aplikasi ini. Kode yang dikenal: '
                    .implode(', ', (array) config('pld.provisioning.roles', [])).'.',
            );
        }

        if ($action === 'setRole') {
            return $this->accountState->setRoles($pldUserId, $peran);
        }

        return ($this->provisionAccount)($subject, $peran);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function log(ProvisioningRequest $request, array $payload, int $status, float $startedAt, string $catatan): void
    {
        $this->logger->inbound(
            endpoint: 'API Provisioning URL ('.$catatan.')',
            request: $request->all(),
            response: $payload,
            status: $status,
            remoteIp: $request->ip(),
            durationMs: (int) round((microtime(true) - $startedAt) * 1000),
        );
    }
}
