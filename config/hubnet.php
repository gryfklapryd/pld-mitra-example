<?php

declare(strict_types=1);

/*
|-------------------------------------------------------------------------------
| Hubnet TIRUAN — konfigurasi IdP palsu
|-------------------------------------------------------------------------------
|
| Aplikasi ini, DI SAMPING perannya sebagai mitra hilir PLD, juga menyediakan
| sebuah tiruan Hubnet: server SSO palsu yang meniru hubnet.kemenhub.go.id secara
| FUNGSIONAL, supaya login SSO di pld-dev bisa diuji langsung tanpa mendaftarkan
| URL ke Pusdatin.
|
| Ia mengimplementasikan tiga permukaan yang benar-benar disentuh pld-user
| (lihat pld-user/internal/service/auth_service.go HubnetSSO):
|
|   GET  /sso/oauth/authorize   → peramban  (halaman login + terbitkan code)
|   POST /sso/oauth/token       → mesin     (tukar code → access token)
|   GET  /sso/api/user          → mesin     (access token → data_user)
|
| Data penggunanya DUMMY, dibuat lewat seeder / `php artisan hubnet:seed`.
|
*/

return [

    /*
    |---------------------------------------------------------------------------
    | Klien OAuth yang terdaftar
    |---------------------------------------------------------------------------
    |
    | Di Hubnet sungguhan, pendaftaran klien (client_id/secret + redirect_uri)
    | dilakukan Pusdatin lewat proses birokrasi. Di tiruan ini, "pendaftaran"
    | cukup mengetik nilainya di sini — itulah seluruh alasan tiruan ini ada.
    |
    | `client_id` dan `client_secret` HARUS sama persis dengan HUBNET_CLIENT_ID /
    | HUBNET_CLIENT_SECRET di pld-user. `redirect_uris` harus memuat
    | BO_DOMAIN + "/hubnet/sso" milik PLD — pld-user mengirim nilai itu saat menukar
    | code, dan OAuth mensyaratkan redirect_uri pada authorize & token IDENTIK.
    |
    */
    'client_id' => env('HUBNET_FAKE_CLIENT_ID'),
    'client_secret' => env('HUBNET_FAKE_CLIENT_SECRET'),

    // Daftar redirect_uri yang diizinkan, dipisah koma. Pencocokannya sama persis
    // (bukan awalan): redirect_uri yang tak sama byte-per-byte ditolak, karena di
    // OAuth sungguhan longgar di sini berarti membuka open redirect / pencurian code.
    'redirect_uris' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('HUBNET_FAKE_REDIRECT_URIS', 'https://pld-dev.ortala-djpu.my.id/hubnet/sso')),
    ))),

    /*
    |---------------------------------------------------------------------------
    | Umur code & access token
    |---------------------------------------------------------------------------
    |
    | Authorization code ditukar dalam hitungan detik (pld-user memanggil token
    | endpoint segera setelah code mendarat), jadi umurnya pendek — memperkecil
    | jendela pakai-ulang bila code bocor lewat riwayat/Referer. Access token
    | hanya dipakai sekali untuk /sso/api/user lalu tak berguna lagi.
    |
    */
    'code_ttl' => (int) env('HUBNET_FAKE_CODE_TTL', 120),
    'token_ttl' => (int) env('HUBNET_FAKE_TOKEN_TTL', 300),

    /*
    |---------------------------------------------------------------------------
    | Label tampilan
    |---------------------------------------------------------------------------
    |
    | Versi yang ditampilkan di footer halaman login, meniru "v1.1.02" pada
    | halaman asli. Murni kosmetik.
    |
    */
    'display_version' => env('HUBNET_FAKE_DISPLAY_VERSION', 'v1.1.02-tiruan'),
];
