<?php

declare(strict_types=1);

return [

    /*
    |---------------------------------------------------------------------------
    | Kredensial arah MASUK — PLD memanggil aplikasi ini
    |---------------------------------------------------------------------------
    |
    | Satu kunci untuk ketiga endpoint kontrak (auth, user validation, tracking).
    | Nilainya ditentukan/didaftarkan di form Aplikasi milik PLD, lalu disalin ke
    | sini. PLD mengirimkannya pada header `Api-Key`.
    |
    | Ini kunci yang sama yang PLD teruskan saat SSO, jadi ia memang bukan rahasia
    | sekali-pakai — perlakukan seperti kredensial layanan biasa: simpan di .env,
    | jangan di-commit.
    |
    */
    'api_key' => env('PLD_API_KEY'),

    /*
    |---------------------------------------------------------------------------
    | Versi kontrak
    |---------------------------------------------------------------------------
    |
    | Dikirim balik pada jawaban tracking. PLD mengabaikan field tak dikenal dan
    | tidak menolak versi yang berbeda hari ini, tetapi kontrak mewajibkan field
    | ini ada sejak versi pertama supaya perbedaan versi terbaca di log kedua
    | belah pihak, bukan ditebak dari bentuk payload.
    |
    */
    'contract_version' => env('PLD_CONTRACT_VERSION', '1.0'),

    /*
    |---------------------------------------------------------------------------
    | Umur auth token SSO
    |---------------------------------------------------------------------------
    |
    | PLD menukar token ini nyaris seketika (langsung dipakai me-redirect member),
    | jadi umur pendek tidak mengganggu siapa pun dan memperkecil jendela pakai
    | ulang bila URL redirect bocor lewat history/referrer.
    |
    */
    'auth_token_ttl' => (int) env('PLD_AUTH_TOKEN_TTL', 60),

    /*
    |---------------------------------------------------------------------------
    | Jalur B — aplikasi ini memanggil PLD (/notif/publish)
    |---------------------------------------------------------------------------
    |
    | Kredensial TERPISAH dari `api_key` di atas, dan arahnya berlawanan:
    | `api_key` diverifikasi aplikasi ini, `service_key` diverifikasi PLD.
    | Diterbitkan sendiri oleh pengelola layanan dari panel "Service Key
    | (Notifikasi)" di backoffice PLD; nilai penuhnya hanya tampil sekali.
    |
    */
    'publish' => [
        'base_url' => rtrim((string) env('PLD_BASE_URL', ''), '/'),
        'service_key' => env('PLD_SERVICE_KEY'),
        'timeout' => (int) env('PLD_PUBLISH_TIMEOUT', 10),
    ],

    /*
    |---------------------------------------------------------------------------
    | Batas-batas kontrak §7
    |---------------------------------------------------------------------------
    |
    | Ditegakkan juga di sisi kita, bukan cuma dipercayakan ke PLD: melampaui
    | batas berarti item DIBUANG diam-diam di seberang sana, dan gejalanya di
    | layar member ("permohonan saya hilang") sama sekali tidak menunjuk ke
    | penyebabnya.
    |
    */
    'limits' => [
        'user_logins_per_request' => 200,
        'items_per_member' => 200,
        'timeline_entries' => 50,
        'attribute_pairs' => 20,
    ],

];
