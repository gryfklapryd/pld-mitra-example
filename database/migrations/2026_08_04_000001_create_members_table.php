<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Member = pengguna aplikasi ini, BUKAN pengguna PLD.
 *
 * `user_login` adalah satu-satunya identitas yang dikenal PLD: ia dipakai pada
 * ketiga endpoint kontrak (auth, user validation, tracking). Nilainya harus sama
 * persis dengan yang diketik member saat menautkan akunnya di portal PLD.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table): void {
            $table->id();

            // Dijadikan unique case-insensitive lewat collation kolom: PLD mencocokkan
            // userLogin dengan strings.ToLower saat memetakan jawaban tracking, jadi
            // "Budi" dan "budi" di sisi kita akan melebur di sisi sana. Lebih baik
            // ditolak di sini daripada menghasilkan dua member yang berebut satu kotak.
            $table->string('user_login', 100)->unique()->collation('utf8mb4_unicode_ci');

            $table->string('name', 150);
            $table->string('email', 190)->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
