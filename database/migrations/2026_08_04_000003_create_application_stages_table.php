<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tahap proses — dipetakan ke `timeline[]` kontrak §4.2.
 *
 * PERHATIKAN: TIDAK ADA kolom `status`.
 *
 * Kontrak menuntut dua invarian yang gampang dilanggar bila status disimpan bebas:
 *   - item BERJALAN wajib punya TEPAT SATU tahap `CURRENT`;
 *   - item TERMINAL (COMPLETED/REJECTED/CANCELLED/EXPIRED/REVOKED) tidak boleh
 *     punya `CURRENT` sama sekali.
 *
 * Item yang melanggarnya dibuang PLD per item — diam-diam dari sudut pandang
 * member, yang hanya melihat permohonannya raib. Maka status DITURUNKAN saat
 * menyusun payload dari `applications.current_stage` + `applications.category`
 * (lihat App\Services\TrackingPayloadService), bukan disimpan. Dengan begitu
 * kedua invarian itu benar secara konstruksi, bukan karena disiplin penulisnya.
 *
 * `occurred_at` tetap disimpan karena ia fakta historis yang tak bisa diturunkan:
 * kontrak mewajibkannya terisi untuk tahap DONE dan CURRENT.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_stages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();

            $table->unsignedSmallInteger('stage');
            $table->string('name', 120);

            // Diisi saat tahap benar-benar tercapai; null selama masih di depan.
            $table->timestamp('occurred_at')->nullable();

            // Unit/jabatan yang menangani — kontrak melarang nama perorangan di sini.
            $table->string('actor', 120)->nullable();

            // Ikut terkirim ke EMAIL member lewat notifikasi PLD, jadi bukan tempat
            // catatan internal (kontrak §8).
            $table->string('note', 500)->nullable();

            $table->timestamps();

            $table->unique(['application_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_stages');
    }
};
