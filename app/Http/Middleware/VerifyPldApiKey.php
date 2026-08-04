<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gerbang ketiga endpoint kontrak: hanya PLD yang boleh memanggil.
 *
 * Header yang dikirim PLD adalah `api-key` (huruf kecil, lihat pld-integration
 * `internal_service.go`). Nama header di HTTP tidak peka besar-kecil huruf, jadi
 * `$request->header('Api-Key')` menangkap keduanya — tetapi hanya karena Laravel
 * menormalkannya, bukan karena kebetulan.
 *
 * Perbandingannya memakai hash_equals: string ini rahasia bersama, dan `===`
 * berhenti pada byte pertama yang berbeda. Selisih waktunya kecil, tetapi
 * endpoint ini boleh dipanggil berulang tanpa batas oleh siapa pun yang tahu
 * alamatnya — persis keadaan yang membuat serangan pengukuran waktu praktis.
 */
final class VerifyPldApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('pld.api_key');
        $provided = (string) $request->header('Api-Key', '');

        if ($expected === '') {
            // Konfigurasi kosong TIDAK BOLEH berarti "semua boleh masuk". Kegagalan
            // konfigurasi yang membuka pintu jauh lebih berbahaya daripada yang
            // menutupnya, dan yang menutup langsung ketahuan saat diuji.
            return $this->deny('Integrasi belum dikonfigurasi di sisi aplikasi.');
        }

        if ($provided === '' || ! hash_equals($expected, $provided)) {
            return $this->deny('Api-Key tidak valid.');
        }

        return $next($request);
    }

    private function deny(string $message): JsonResponse
    {
        // Kontrak §4.1 menetapkan 400 dengan {"message": "..."} untuk kunci yang
        // tidak sah — bukan 401. Bentuknya mengikuti kontrak, bukan kebiasaan REST.
        return new JsonResponse(['message' => $message], Response::HTTP_BAD_REQUEST);
    }
}
