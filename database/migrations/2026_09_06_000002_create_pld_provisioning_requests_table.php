<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ingatan idempotensi untuk `API Provisioning URL` (kontrak §7).
 *
 * Kontraknya tegas: bila PLD mengulang permintaan yang gagal, `requestId` yang
 * dikirim SAMA PERSIS dengan percobaan sebelumnya, dan aplikasi ini wajib
 * mengembalikan jawaban yang sama alih-alih mengerjakannya lagi.
 *
 * Yang dijaga bukan kerapian melainkan akun ganda. Bayangkan urutan yang
 * benar-benar terjadi di jaringan: kita membuat akun, jawaban 200 kita hilang
 * di tengah jalan, PLD menganggapnya timeout lalu mengulang. Tanpa tabel ini,
 * percobaan kedua membuat akun KEDUA untuk orang yang sama — dan yang pertama
 * jadi yatim, tak dikenal PLD, tapi hidup dan bisa dimasuki.
 *
 * `response` menyimpan jawaban APA ADANYA, bukan sekadar penanda "pernah
 * diproses". Kontrak meminta jawaban yang sama dikembalikan, dan jawaban itu
 * memuat `userLogin` yang dipakai PLD untuk SSO — menyusunnya ulang dari
 * keadaan sekarang bisa menghasilkan nilai yang berbeda bila member sempat
 * disunting di antara dua percobaan.
 *
 * Retensi minimal 7 hari (§7). Pembersihannya diserahkan ke perawatan berkala;
 * tabel ini tumbuh sangat lambat — satu baris per permintaan provisioning.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pld_provisioning_requests', function (Blueprint $table): void {
            $table->id();

            // Unique: inilah seluruh gunanya tabel ini. Tanpa batasan di tingkat
            // basis data, dua percobaan yang tiba berbarengan sama-sama lolos
            // pemeriksaan "sudah ada?" lalu sama-sama membuat akun.
            $table->string('request_id', 64)->unique();

            $table->string('action', 32);
            $table->string('pld_user_id', 64)->nullable()->index();
            $table->json('response');
            $table->unsignedSmallInteger('status');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pld_provisioning_requests');
    }
};
