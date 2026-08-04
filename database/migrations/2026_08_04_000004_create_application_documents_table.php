<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dokumen hasil proses — `documents[]` kontrak §4.4.
 *
 * Hanya TAUTAN, tidak pernah isi berkas. Kontrak menyebutnya eksplisit, dan
 * alasannya bukan sekadar ukuran payload: berkas yang dikirim ke PLD menjadi
 * salinan kedua yang harus ikut dihapus saat akses member berakhir, sementara
 * tautan tetap tunduk pada kendali akses aplikasi ini sendiri.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();

            $table->string('label', 120);
            $table->string('url', 500);
            $table->timestamp('issued_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_documents');
    }
};
