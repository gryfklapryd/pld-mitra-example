<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom yang dibutuhkan `API Provisioning URL` (kontrak provisioning v1.0).
 *
 * Sampai sekarang member hanya bisa lahir dari seeder atau panel aplikasi ini
 * sendiri. Kontrak provisioning menambah jalur keempat: PLD yang memintanya
 * dibuatkan, saat seorang member membuka layanan ini untuk pertama kali.
 *
 * -------------------------------------------------------------------------
 * KENAPA `pld_user_id`
 * -------------------------------------------------------------------------
 * Kontrak §10 meminta pencocokan dilakukan lewat `pldUserId` LEBIH DULU, baru
 * NIP/email. Alasannya ada di §8: member boleh berganti email di PLD, dan
 * pencocokan yang bersandar pada email akan menganggapnya orang baru lalu
 * membuatkan akun kedua. `pld_user_id` tak pernah berubah seumur hidup akun.
 *
 * Nullable, karena member yang sudah ada di sini lahir sebelum PLD mengenalnya
 * — mereka baru terisi bila kelak ditautkan.
 *
 * -------------------------------------------------------------------------
 * KENAPA `email_verified_at`, DAN KENAPA IA MENENTUKAN JAWABAN
 * -------------------------------------------------------------------------
 * Inilah yang memisahkan `EXISTS_LINKED` dari `EXISTS_UNVERIFIED` (kontrak
 * §4.1.1). Bila aplikasi ini pernah mengizinkan orang mendaftar dengan email
 * yang tak pernah dibuktikan kepemilikannya, maka mencocokkan lewat email saja
 * BUKAN bukti bahwa itu orang yang sama — menjawab `EXISTS_LINKED` di situ
 * berarti menyerahkan akun ini kepada siapa pun yang mendaftar di PLD memakai
 * email yang sama.
 *
 * Kolomnya NULL untuk semua member lama, dan itu disengaja: kita memang tidak
 * pernah memverifikasi mereka, jadi jawaban jujurnya adalah "belum terverifikasi".
 * Menandai mereka verified demi memuluskan uji coba akan membalik keputusan
 * keamanan yang justru jadi inti kontraknya.
 *
 * -------------------------------------------------------------------------
 * KENAPA `roles` JSON, BUKAN TABEL PIVOT
 * -------------------------------------------------------------------------
 * Aplikasi tiruan ini tidak punya sistem otorisasi sendiri — perannya hanya
 * disimpan lalu dipantulkan kembali. Tabel pivot beserta tabel master peran
 * akan memberi kesan aplikasi ini memvalidasi kewenangan, padahal tidak.
 * Daftar peran yang SAH tetap dijaga di config (lihat ProvisioningController).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->string('pld_user_id', 64)->nullable()->unique()->after('id');
            $table->timestamp('email_verified_at')->nullable()->after('email');
            $table->json('roles')->nullable()->after('is_active');
            // Jejak bahwa akun ini lahir dari permintaan PLD, bukan dari seeder
            // maupun panel. Dipakai saat menelusuri "dari mana akun ini datang".
            $table->timestamp('provisioned_at')->nullable()->after('roles');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->dropUnique(['pld_user_id']);
            $table->dropColumn(['pld_user_id', 'email_verified_at', 'roles', 'provisioned_at']);
        });
    }
};
