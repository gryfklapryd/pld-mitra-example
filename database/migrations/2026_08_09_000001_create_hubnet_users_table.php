<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Direktori identitas Hubnet TIRUAN — data DUMMY.
 *
 * Ini bukan tabel milik aplikasi mitra (itu `members`). Ini "basis data pegawai
 * & badan usaha" milik Hubnet-palsu: apa yang di dunia nyata dipegang Pusdatin.
 * Kolomnya mengikuti bentuk payload `/sso/api/user` yang dibaca pld-user
 * (lihat pld-user HubnetDataUser) — sengaja tidak seragam antar tipe:
 *
 *   type 1 (pegawai) : NIP, NIK, unit, kode_unit, golongan/pangkat, jabatan, status
 *   type 2 (OSS)     : username=NIB (atau email), NIK, NPWP — tanpa penanda status
 *   type 3 (lainnya) : email — ditolak pld-user hari ini (ErrForbidden), disediakan
 *                      supaya penolakan itu bisa DILIHAT, bukan ditebak.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hubnet_users', function (Blueprint $table): void {
            $table->id();

            // 1=pegawai, 2=OSS, 3=lainnya. Menentukan `type` pada payload dan radio
            // mana yang harus dipilih di halaman login.
            $table->unsignedTinyInteger('type');

            // Yang diketik di kolom USERNAME halaman login: NIP / NIB / email.
            // Unik per tipe — NIP boleh saja secara kebetulan sama dengan sebuah NIB.
            $table->string('username', 190);

            // Kata sandi DUMMY. Tiruan ini tidak pernah menerima kredensial Kemenhub
            // sungguhan; nilai ini di-seed dan hanya berlaku di lingkungan uji.
            $table->string('password');

            $table->string('name', 190);
            $table->string('email', 190)->nullable();

            // Identitas kuat yang dijamin Hubnet. pld-user mengenali orang lewat
            // NIP/NIB dan mengaitkan akun lewat NIK — bukan lewat email.
            $table->string('nip', 32)->nullable();
            $table->string('nik', 32)->nullable();
            $table->string('npwp', 32)->nullable();
            $table->string('phone', 32)->nullable();

            // Atribut pegawai (type 1). kode_unit '005000000000000' = DJPU, dipakai
            // pld-user hanya sebagai saringan KELAYAKAN ADMIN, bukan saringan login.
            $table->string('unit', 190)->nullable();
            $table->string('kode_unit', 32)->nullable();
            $table->string('golongan', 32)->nullable();
            $table->string('pangkat', 64)->nullable();
            $table->string('jabatan_fungsional', 190)->nullable();
            $table->string('jabatan_struktural', 190)->nullable();

            // Penanda nonaktif Hubnet (B5). pld-user memblokir login bila status=0
            // atau is_deleted=1. Default: aktif.
            $table->boolean('status')->default(true);
            $table->boolean('is_deleted')->default(false);

            $table->timestamps();

            $table->unique(['type', 'username']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hubnet_users');
    }
};
