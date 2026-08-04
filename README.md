# PEL — Aplikasi Mitra PLD (contoh implementasi kontrak)

Aplikasi Laravel yang bertindak sebagai **layanan mitra** Pusat Layanan Data (PLD).
Ia mengimplementasikan ketiga endpoint kontrak integrasi dan bisa dipakai untuk
dua hal:

1. **Menguji integrasi PLD ujung ke ujung** tanpa menunggu proses perizinan
   sungguhan berjalan berminggu-minggu — ada panel operator untuk menggerakkan
   status permohonan dan melihat notifikasi terbit di portal PLD.
2. **Rujukan kode** bagi tim mitra yang akan menulis implementasinya sendiri.

Dokumen kontraknya: `pld-user/docs/integrasi-tracking-notifikasi.md` (v1.1).

---

## Apa yang diimplementasikan

| Endpoint | Arah | Rute di aplikasi ini |
|---|---|---|
| **API Auth URL** | PLD → kita | `POST /api/pld/auth` |
| **API User Validation URL** | PLD → kita | `POST /api/pld/user/validation` |
| **API Tracking URL** (Jalur A) | PLD → kita | `POST /api/pld/tracking` |
| **Redirect URL** (pendaratan SSO) | peramban member | `GET /sso?pld_auth=<token>` |
| **detailUrl** tiap item tracking | peramban member | `GET /permohonan/{externalRef}` |
| **Notification Publish** (Jalur B) | kita → PLD | `App\Services\PldNotificationClient` |

Ketiga endpoint arah masuk dijaga satu middleware (`VerifyPldApiKey`) yang
memeriksa header `Api-Key`.

---

## Menjalankan

```bash
composer install
npm install && npm run build

cp .env.example .env
php artisan key:generate

# sesuaikan DB_* di .env, lalu:
php artisan migrate --seed
php artisan serve --port=8090
```

Buka `http://localhost:8090`. Panel operator ada di `/admin`.

Data contoh yang ikut ter-seed:

| user_login | password | Isi |
|---|---|---|
| `john_doe` | `rahasia123` | satu proses `ACTION_REQUIRED` (tahap 3/5) + satu `COMPLETED` |
| `siti_aminah` | `rahasia123` | satu proses `IN_PROGRESS` (tahap 2/5) |

---

## Mendaftarkan ke PLD

Isi form **Aplikasi** di backoffice PLD (hanya Super Admin) dengan:

| Field | Nilai |
|---|---|
| Redirect URL | `http://localhost:8090/sso?pld_auth=` |
| API Auth URL | `http://localhost:8090/api/pld/auth` |
| API User Validation URL | `http://localhost:8090/api/pld/user/validation` |
| **API Tracking URL** | `http://localhost:8090/api/pld/tracking` |
| API Key | nilai bebas ≥16 karakter — **salin ke `PLD_API_KEY` di `.env`** |

Lalu di `.env` aplikasi ini:

```dotenv
PLD_API_KEY=<sama persis dengan API Key di form Aplikasi PLD>

# Hanya bila memakai Jalur B:
PLD_BASE_URL=https://pld.example.go.id
PLD_SERVICE_KEY=<keyId>.<secret>
```

`Service Key` diterbitkan sendiri oleh pengelola layanan dari panel **Service Key
(Notifikasi)** di halaman Aplikasi backoffice PLD. Nilai penuhnya hanya tampil
sekali saat dibuat.

> **PLD berjalan di Docker/cluster?** `localhost` di sana menunjuk ke container
> PLD sendiri, bukan ke mesin Anda. Pakai `http://host.docker.internal:8090/...`
> atau alamat IP LAN Anda.

Agar member ikut disinkronkan, tiga syarat harus terpenuhi (kontrak §6):
aplikasi mengisi `API Tracking URL` **dan** `API Key`; member berstatus akses
`APPROVED` **dan** `user_login`-nya sudah tertaut di PLD; serta sudah pernah ada
satu sinkronisasi berhasil sebelumnya.

---

## Mencoba tanpa PLD

```bash
KEY="isi-dengan-PLD_API_KEY-anda"

# API Auth URL
curl -X POST http://localhost:8090/api/pld/auth \
  -H "Api-Key: $KEY" -H "Content-Type: application/json" \
  -d '{"user_login":"john_doe"}'

# API User Validation URL
curl -X POST http://localhost:8090/api/pld/user/validation \
  -H "Api-Key: $KEY" -H "Content-Type: application/json" \
  -d '{"user_login":"john_doe","password":"rahasia123"}'

# API Tracking URL
curl -X POST http://localhost:8090/api/pld/tracking \
  -H "Api-Key: $KEY" -H "Content-Type: application/json" \
  -d '{"contractVersion":"1.0","userLogins":["john_doe","siti_aminah"]}'
```

Setiap panggilan tercatat di **`/admin/log-integrasi`**, lengkap dengan payload
masuk dan keluar. Layar itu menjawab pertanyaan pertama pada setiap kegagalan
integrasi: *"PLD sudah memanggil belum?"*

---

## Enam aturan kontrak yang paling mudah dilanggar

Aturan-aturan ini ditegakkan PLD **per item**. Item yang melanggarnya dibuang
diam-diam — tidak ada galat yang kembali ke aplikasi Anda, dan dari kursi member
permohonannya sekadar tidak pernah muncul. Karena itu di aplikasi ini semuanya
dibuat benar secara konstruksi, bukan lewat kehati-hatian.

**1. Item berjalan wajib punya tepat satu tahap `CURRENT`; item terminal tidak
boleh punya sama sekali.**
Status tahap **tidak disimpan** di basis data — tabel `application_stages` sengaja
tidak punya kolom `status`. Ia diturunkan dari `current_stage` + `category` di
`TrackingPayloadService::stageStatusFor()`. Dengan begitu kedua invarian benar
dengan sendirinya.

**2. `actionRequired` wajib ada saat `ACTION_REQUIRED`, dan wajib TIDAK ADA selain
itu.**
Arah kedua yang sering terlewat. Instruksi yang tertinggal di basis data setelah
kategori berpindah akan menggugurkan **seluruh** item. `actionRequiredFor()`
memeriksa kategori lebih dulu, bukan sekadar "kalau kolomnya terisi, kirim".

**3. Tahap `DONE`/`CURRENT` wajib punya `occurredAt`.**
Satu `null` di sini membuang seluruh item. `occurredAtFor()` memberi nilai
cadangan alih-alih membiarkan null lolos — kehilangan satu stempel waktu jauh
lebih ringan daripada kehilangan seluruh permohonan dari layar member.

**4. Waktu harus RFC3339.**
PLD memakai `time.Parse(time.RFC3339)`. Format lain gagal diurai dan itemnya
ditolak.

**5. Jawaban adalah gambaran utuh, dan `200 items:[]` menghapus cache.**
Sinkronisasi yang berhasil **mengganti**, bukan menambah. Karena itu kegagalan
internal di `TrackingController` dibalas **500, bukan 200 dengan items kosong** —
membalas kosong berarti menghapus seluruh riwayat member dari portal PLD hanya
karena satu galat sesaat di sisi kita.

**6. `userLogin` dicocokkan tanpa memandang besar-kecil huruf.**
PLD me-`lower` nilainya saat memetakan jawaban ke member. Mencari dengan
perbandingan case-sensitive membuat kita menjawab untuk `"Budi"` sementara PLD
mencari kunci `"budi"` — item terkirim, tapi tak pernah sampai ke siapa pun.

Panel `/admin/permohonan/{id}` menampilkan **pratinjau payload** persis seperti
yang akan diterima PLD, beserta hitungan tahap `CURRENT` dan keadaan
`actionRequired`. Periksa di situ sebelum menunggu siklus 30 menit.

---

## Perubahan mana yang mengirim email?

Notifikasi Jalur A terbit otomatis dari perbandingan jawaban tracking Anda
(kontrak §6). Yang perlu diingat operator:

| Perubahan | Notifikasi | Email? |
|---|---|---|
| `externalRef` baru muncul | "mulai dilacak" | tidak |
| → `ACTION_REQUIRED` | memuat instruksi + batas waktu | **ya** |
| → `COMPLETED` | memuat nama dokumen hasil | **ya** |
| → `REJECTED`/`CANCELLED`/`EXPIRED`/`REVOKED` | memuat `note` tahap terakhir | **ya** |
| → `IN_PROGRESS` (dari ACTION_REQUIRED) | "diproses kembali" | tidak |
| `currentStage` naik | memuat nama tahap | tidak |

Yang **tidak** memicu apa pun: `updatedAt` berubah, `statusLabel` berubah
sendirian, `currentStage` mundur, dan item yang berhenti dikirim.

Sinkronisasi **pertama** untuk seorang member tidak pernah menerbitkan
notifikasi — jawaban pertama disimpan sebagai titik awal perbandingan.

---

## Jalur A vs Jalur B — jangan dipakai bersamaan untuk hal yang sama

- **Jalur A (tracking)** untuk apa pun yang punya tahapan. Notifikasinya terbit
  sendiri; Anda tidak menulis kode pengiriman apa pun.
- **Jalur B (`/notif/publish`)** hanya untuk pemberitahuan **di luar** proses:
  pengumuman pemeliharaan, akun ditangguhkan, undangan.

Memakai keduanya untuk peristiwa yang sama menghasilkan notifikasi ganda.
Pembagian ini **belum dijaga PLD** — ia sepenuhnya tanggung jawab sisi mitra.

Catatan Jalur B: belum ada *idempotency key*, jadi kirim-ulang atas kiriman yang
sebenarnya sudah diterima menghasilkan notifikasi ganda. Untuk pemberitahuan
tidak kritis, lebih aman melepaskannya daripada mengulanginya membabi buta.

---

## Struktur kode

```
app/
  Actions/          IssueSsoToken, RedeemSsoToken, ValidateMemberCredentials,
                    CreateApplication, AdvanceApplicationStage, ChangeApplicationCategory
  DTOs/             Bentuk payload kontrak — serialisasinya terkurung di satu tempat
  Enums/            TrackingCategory (7 nilai tertutup), StageStatus
  Http/
    Controllers/Api/    Tiga endpoint kontrak, tipis
    Middleware/         VerifyPldApiKey
    Requests/           Seluruh validasi
  Repositories/     Seluruh query Eloquent, di balik Contracts/
  Services/         TrackingPayloadService (inti kontrak), PldNotificationClient,
                    IntegrationLogger
  Support/          PayloadRedactor
```

Seluruh kontrak diikat ke implementasinya di `App\Providers\DomainServiceProvider`.

---

## Uji

```bash
php artisan test
```

38 uji, mencakup ketiga endpoint kontrak, seluruh invarian di atas, penukaran
token SSO (termasuk sifat sekali-pakai), dan pemetaan kode jawaban Jalur B.

Uji memakai basis data terpisah (`pld_mitra_example_test`, lihat `phpunit.xml`)
karena `RefreshDatabase` memangkas seluruh tabel — mengarahkannya ke basis data
kerja akan menghapus data contoh setiap kali uji dijalankan.

Satu uji yang sebaiknya tidak pernah dihapus:
`UserValidationEndpointTest::password_tidak_pernah_tersimpan_apa_adanya_di_log`.
`API User Validation URL` menerima password member dalam bentuk mentah — itu
memang bentuk kontraknya. Bila penyamarannya jebol, basis data aplikasi ini
menjadi tempat penyimpanan password bersih milik pengguna sistem lain.

---

## Sebelum dipakai sungguhan

Aplikasi ini contoh. Yang **wajib** diubah untuk pemakaian nyata:

- **Panel `/admin` tanpa autentikasi sama sekali.** Ia bisa mengubah status
  permohonan siapa pun. Taruh di balik middleware `auth` + otorisasi peran.
- **HTTPS wajib.** `API User Validation URL` menerima password member.
- Batas laju pada ketiga endpoint arah masuk.
- Kebijakan retensi `integration_logs` — ia tumbuh setiap 30 menit selamanya.
