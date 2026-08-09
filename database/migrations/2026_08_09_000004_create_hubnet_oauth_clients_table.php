<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Klien OAuth yang boleh memakai SSO Hubnet TIRUAN.
 *
 * Sebelumnya klien tunggal dibaca dari config/hubnet.php (dari env). Tabel ini
 * memindahkannya ke basis data supaya bisa dikelola lewat panel operator —
 * mendaftarkan "aplikasi baru" tak lagi berarti menyunting env dan me-restart.
 *
 * `client_secret` sengaja disimpan APA ADANYA (bukan hash). Ini IdP tiruan untuk
 * uji: nilai penuh secret memang perlu ditampilkan agar bisa disalin ke
 * HUBNET_CLIENT_SECRET milik aplikasi klien (pld-user). Verifikasinya tetap
 * hash_equals. Jangan tiru pola ini di IdP sungguhan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hubnet_oauth_clients', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 190);
            $table->string('client_id', 190)->unique();
            $table->string('client_secret', 190);

            // Daftar redirect_uri yang diizinkan — dicocokkan SAMA PERSIS saat
            // authorize & saat menukar code (pengaman pencurian code).
            $table->json('redirect_uris');

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hubnet_oauth_clients');
    }
};
