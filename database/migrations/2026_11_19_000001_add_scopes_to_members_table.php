<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Keanggotaan lingkup dari `scopes` pada `createAccount` dan `setRole` (dokumen provisioning bagian
 * 4.1.7), misalnya lembaga diklat tempat member memegang peran LEMBAGA.
 *
 * JSON dengan alasan yang sama dengan `roles`: aplikasi tiruan ini hanya menyimpan dan memantulkan
 * keanggotaannya, tanpa sistem otorisasi sendiri. NULL berarti PLD belum pernah mengirim `scopes`
 * untuk member ini, berbeda dari daftar kosong yang berarti seluruh keanggotaan dilepas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->json('scopes')->nullable()->after('roles');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->dropColumn('scopes');
        });
    }
};
