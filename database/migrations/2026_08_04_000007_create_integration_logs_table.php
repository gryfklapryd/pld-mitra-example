<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak setiap panggilan integrasi, DUA ARAH.
 *
 * Ini bukan kemewahan. Saat integrasi tidak berjalan, pertanyaan pertama selalu
 * "PLD sudah memanggil belum?" — dan tanpa tabel ini jawabannya cuma bisa ditebak
 * dari log server kedua belah pihak yang tak pernah dibaca bersamaan. Sinkronisasi
 * terjadwal berjalan tiap 30 menit tanpa ada manusia yang menonton; kalau
 * panggilannya tidak meninggalkan jejak yang bisa dibuka lewat UI, kegagalan baru
 * ketahuan saat member mengeluh.
 *
 * `direction` memisahkan dua hal yang gampang tertukar:
 *   - INBOUND  = PLD memanggil kita (auth / user validation / tracking)
 *   - OUTBOUND = kita memanggil PLD (Jalur B /notif/publish)
 *
 * Password pada user validation TIDAK PERNAH ditulis apa adanya ke sini —
 * lihat App\Support\PayloadRedactor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_logs', function (Blueprint $table): void {
            $table->id();

            $table->string('direction', 10);
            $table->string('endpoint', 190);
            $table->unsignedSmallInteger('http_status')->nullable();

            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();

            $table->string('error_message', 500)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('remote_ip', 45)->nullable();

            $table->timestamps();

            $table->index(['direction', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
    }
};
