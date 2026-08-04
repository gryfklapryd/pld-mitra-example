<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Keterangan khas domain — `attributes[]` kontrak §4.6.
 *
 * Tempat resmi untuk fakta yang tidak punya field sendiri di kontrak ("Nomor Izin
 * Berjalan", "Jenis Angkutan", …). Kontrak menegaskan: field karangan sendiri di
 * luar §4 diabaikan PLD tanpa galat — jadi menaruhnya di sini adalah satu-satunya
 * cara agar informasinya benar-benar sampai ke layar member.
 *
 * `value` sudah berupa teks siap tampil; PLD tidak memformat ulang apa pun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_attributes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();

            $table->string('label', 80);
            $table->string('value', 200);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_attributes');
    }
};
