<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Token auto-login yang diterbitkan lewat `API Auth URL`.
 *
 * Alurnya: PLD memanggil endpoint auth kita dengan `user_login` → kita terbitkan
 * token → PLD meredirect member ke `Redirect URL` + token → aplikasi ini menukar
 * token itu menjadi sesi.
 *
 * Tiga sifat yang membuatnya bukan sekadar string acak:
 *
 * 1. Disimpan sebagai HASH, bukan nilai mentah. Nilai mentah hanya hidup di
 *    jawaban HTTP dan di URL redirect. Bocornya isi tabel ini karena SQL injection
 *    atau dump basis data karenanya tidak langsung menjadi izin masuk.
 * 2. SEKALI PAKAI (`used_at`). Token ini mendarat di URL — tempat yang bocor lewat
 *    riwayat peramban, header Referer, dan log proxy. Sekali pakai membuat salinan
 *    yang bocor itu sudah mati saat ditemukan.
 * 3. Berumur pendek (config `pld.auth_token_ttl`), karena penukarannya terjadi
 *    dalam hitungan detik.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sso_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();

            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sso_tokens');
    }
};
