<?php

declare(strict_types=1);

namespace App\Actions;

/**
 * Hasil pemeriksaan kredensial.
 *
 * Tiga keadaan, bukan boolean, karena kontrak memetakannya ke dua kode HTTP yang
 * berbeda — dan pld-integration di sisi PLD memperlakukan keduanya berbeda pula
 * (400 dihitung sebagai percobaan gagal milik member; 5xx dianggap gangguan
 * aplikasi dan TIDAK dihitung).
 */
enum ValidationOutcome
{
    /** Kredensial cocok → 200 {"is_valid": true} */
    case Valid;

    /** Akun ada, password salah / akun nonaktif → 200 {"is_valid": false} */
    case Invalid;

    /** `user_login` tidak dikenal di aplikasi ini → 400 */
    case NotFound;
}
