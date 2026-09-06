<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\ProvisioningRequestStoreContract;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class ProvisioningRequestStore implements ProvisioningRequestStoreContract
{
    public function find(string $requestId): ?array
    {
        $row = DB::table('pld_provisioning_requests')
            ->where('request_id', $requestId)
            ->first(['response', 'status']);

        if ($row === null) {
            return null;
        }

        return [
            'response' => (array) json_decode((string) $row->response, true),
            'status' => (int) $row->status,
        ];
    }

    public function remember(string $requestId, string $action, ?string $pldUserId, array $response, int $status): array
    {
        try {
            DB::table('pld_provisioning_requests')->insert([
                'request_id' => $requestId,
                'action' => $action,
                'pld_user_id' => $pldUserId,
                'response' => json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return ['response' => $response, 'status' => $status];
        } catch (QueryException $e) {
            // Kembar: percobaan lain menyimpan lebih dulu untuk requestId yang sama.
            //
            // Ditangkap, bukan dibiarkan naik jadi 500. Dua percobaan berbarengan
            // adalah keadaan yang NORMAL pada jalur ini — PLD mengulang permintaan
            // yang jawabannya hilang di jaringan, dan pengulangan itu bisa tiba
            // sebelum percobaan pertama selesai. Yang dikembalikan adalah jawaban
            // PEMENANG, sehingga kedua penelepon melihat hasil yang sama persis
            // seperti yang dijanjikan kontrak §7.
            $existing = $this->find($requestId);

            if ($existing !== null) {
                return $existing;
            }

            throw $e;
        }
    }
}
