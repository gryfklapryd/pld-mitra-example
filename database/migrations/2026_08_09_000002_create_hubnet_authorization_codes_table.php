<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Authorization code OAuth yang diterbitkan tiruan Hubnet saat login berhasil.
 *
 * Sifatnya sama dengan sso_tokens milik aplikasi ini, dan alasannya sama:
 *
 * 1. Disimpan sebagai HASH — nilai mentahnya hanya hidup di URL redirect ke PLD.
 * 2. SEKALI PAKAI (`used_at`) — code mendarat di URL, tempat yang bocor lewat
 *    riwayat peramban & Referer; sekali pakai membuat salinan bocor sudah mati.
 * 3. Berumur pendek (config `hubnet.code_ttl`).
 *
 * `redirect_uri` disimpan bersama code karena OAuth mengharuskan redirect_uri
 * pada penukaran token sama persis dengan yang dipakai saat code diterbitkan —
 * pengaman terhadap code yang dicuri lalu ditukar ke tujuan lain.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hubnet_authorization_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hubnet_user_id')->constrained('hubnet_users')->cascadeOnDelete();

            $table->string('code_hash', 64)->unique();
            $table->string('client_id', 190);
            $table->string('redirect_uri', 500);

            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hubnet_authorization_codes');
    }
};
