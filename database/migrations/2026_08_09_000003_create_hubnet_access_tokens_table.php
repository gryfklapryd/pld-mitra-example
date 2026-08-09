<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Access token Hubnet TIRUAN — hasil penukaran authorization code.
 *
 * pld-user memakainya sekali untuk `GET /sso/api/user` (header
 * `Authorization: Bearer <token>`), lalu tak menyentuhnya lagi. Seperti code,
 * disimpan sebagai hash dan berumur pendek (config `hubnet.token_ttl`); nilai
 * mentahnya hanya hidup di jawaban token endpoint dan di header permintaan
 * userinfo — tidak pernah tersimpan apa adanya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hubnet_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hubnet_user_id')->constrained('hubnet_users')->cascadeOnDelete();

            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hubnet_access_tokens');
    }
};
