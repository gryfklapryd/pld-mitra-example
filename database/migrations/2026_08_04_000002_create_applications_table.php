<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Application = satu proses milik member (permohonan/perizinan/sertifikasi).
 *
 * Inilah yang dilaporkan ke PLD sebagai satu entri `items[]` pada kontrak §4.
 *
 * Dua keputusan bentuk yang sengaja diambil di sini:
 *
 * 1. `external_ref` unik dan TIDAK PERNAH berubah sepanjang hidup proses. PLD
 *    memakainya sebagai kunci pembaruan; mengubahnya di tengah jalan membuat PLD
 *    membaca satu proses sebagai "yang lama hilang, ada yang baru muncul", yang
 *    menerbitkan notifikasi palsu "mulai dilacak".
 *
 * 2. `actionRequired` disimpan sebagai tiga kolom biasa, bukan tabel/JSON
 *    terpisah. Kontrak menegakkan aturan dua arah — WAJIB ada saat category
 *    ACTION_REQUIRED dan WAJIB tidak ada selain itu — jadi ia bukan koleksi,
 *    melainkan atribut kondisional dari baris ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table): void {
            $table->id();

            $table->string('external_ref', 128)->unique();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();

            $table->string('label', 200);
            $table->string('category', 20);
            $table->string('status_label', 120);

            $table->timestamp('submitted_at');

            // Dipetakan ke `updatedAt` kontrak: KAPAN STATUS BERUBAH, bukan kapan baris
            // ini tersentuh. Dipisahkan dari `updated_at` bawaan Eloquent justru karena
            // keduanya beda arti — menyunting typo pada statusLabel tidak boleh tampak
            // sebagai "prosesnya bergerak".
            $table->timestamp('status_changed_at');

            $table->timestamp('expires_at')->nullable();

            $table->unsignedSmallInteger('current_stage')->default(1);
            $table->unsignedSmallInteger('total_stages');

            // actionRequired (kontrak §4.3) — hanya terisi saat category ACTION_REQUIRED.
            $table->string('action_instruction', 500)->nullable();
            $table->timestamp('action_due_at')->nullable();
            $table->string('action_url', 500)->nullable();

            // contact (kontrak §4.5) — unit/jabatan, BUKAN nama perorangan.
            $table->string('contact_unit', 200)->nullable();
            $table->string('contact_email', 190)->nullable();
            $table->string('contact_phone', 50)->nullable();

            $table->timestamps();

            // Jalur query satu-satunya yang panas: "seluruh proses milik daftar member ini".
            $table->index(['member_id', 'status_changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
