# Hubnet TIRUAN — IdP palsu untuk uji SSO

Aplikasi ini, di samping perannya sebagai mitra hilir PLD, kini juga menyediakan
**tiruan Hubnet**: server SSO palsu yang meniru `hubnet.kemenhub.go.id` secara
**fungsional**, supaya login SSO di portal PLD (`pld.greyfikal.web.id`) bisa diuji langsung
tanpa mendaftarkan URL ke Pusdatin.

Ia mengimplementasikan tepat tiga permukaan yang disentuh pld-user
(`internal/service/auth_service.go` → `HubnetSSO`):

| Permukaan | Arah | Rute di sini |
|---|---|---|
| Authorize (halaman login) | peramban | `GET /sso/oauth/authorize` |
| Token exchange | mesin (pld-user) | `POST /sso/oauth/token` |
| Userinfo | mesin (pld-user) | `GET /sso/api/user` |

Data penggunanya **DUMMY**, dibuat lewat seeder / `php artisan hubnet:seed`.
Halaman login **hanya menerima akun contoh yang di-seed** — tidak pernah menerima
kredensial Kemenhub sungguhan.

---

## Alur ujung ke ujung

```
1. Orang buka https://pld.greyfikal.web.id/login → klik "SSO HUBNET"
2. Peramban lompat ke  {HUBNET_DOMAIN}/sso/oauth/authorize?client_id=…&redirect_uri=…&response_type=code
3. Halaman login TIRUAN → pilih salah satu akun contoh → Log In
4. TIRUAN terbitkan `code`, redirect balik ke  {BO_DOMAIN}/hubnet/sso?code=…&state=…
5. FE PLD → POST /api/sso-internal → pld-user  POST /user/api/v1/auth/hubnet
6. pld-user → POST {HUBNET_DOMAIN}/sso/oauth/token   → {token_type, access_token}
7. pld-user → GET  {HUBNET_DOMAIN}/sso/api/user      → {data_user:{…}}
8. pld-user provisioning → sesi PLD terbit → masuk.
```

`{HUBNET_DOMAIN}` = `https://sso.greyfikal.web.id` (deployment aplikasi ini).

> **Pindah host, 2026-08-27.** Tiruan ini dulu hidup di
> `https://202-155-132-181.nip.io`. FortiGate di jaringan VPS PLD memblokir
> **setiap permintaan bernama** ke IP `202.155.132.181` — diuji dengan
> `curl --resolve` yang memotong DNS sepenuhnya, dan `nip.io`,
> `sso.greyfikal.web.id`, maupun nama karangan sama-sama dibalas **403**. Hanya
> permintaan tanpa hostname yang lolos, dan itu tak terpakai karena sertifikatnya
> hanya untuk nama lama. Menambah A record cuma memberi halaman blokir itu nama baru.
>
> Yang gugur bukan kaki peramban — peramban pemakai ada di luar jaringan VPS dan
> selalu bisa menjangkau IdP. Yang gugur adalah **langkah 6 & 7** di alur bawah:
> pld-user memanggil `/sso/oauth/token` dan `/sso/api/user` dari DALAM VPS. Login
> tampak mulus sampai layar terakhir lalu gagal saat tukar-kode, dengan galat yang
> menunjuk ke IdP alih-alih ke jaringan.
>
> Karena itu tiruan ini sekarang **serumah dengan PLD** di VPS `103.141.234.16`:
> panggilan mesin-ke-mesin jadi hairpin lewat nginx host dan tak pernah menyentuh
> FortiGate.

---

## Nilai yang harus disetel (turnkey)

### 1. Di aplikasi ini (`.env` — di VPS PLD: `/opt/pld-hubnet/env`)

```dotenv
HUBNET_FAKE_CLIENT_ID=0bc24bbf-4912-4621-aa00-361795f3e18e
HUBNET_FAKE_CLIENT_SECRET=1d4274360762607e17e89a6cd453cdd2c6f66000be4bddbc
HUBNET_FAKE_REDIRECT_URIS=https://pld.greyfikal.web.id/hubnet/sso
HUBNET_FAKE_CODE_TTL=120
HUBNET_FAKE_TOKEN_TTL=300
```

> Ganti `client_id`/`client_secret` dengan nilai Anda sendiri bila mau — yang
> penting **sama persis** dengan yang disetel di pld-user (butir 2).
> `HUBNET_FAKE_REDIRECT_URIS` harus memuat `BO_DOMAIN + "/hubnet/sso"` milik PLD.

### 2. Di **pld-user** (secret cluster / `secrets-templates/pld-user.env`)

```dotenv
HUBNET_DOMAIN=https://sso.greyfikal.web.id
HUBNET_CLIENT_ID=0bc24bbf-4912-4621-aa00-361795f3e18e
HUBNET_CLIENT_SECRET=1d4274360762607e17e89a6cd453cdd2c6f66000be4bddbc
BO_DOMAIN=https://pld.greyfikal.web.id           # sudah terisi
TLS_INSECURE_SKIP_VERIFY=                          # BIARKAN KOSONG — sertifikatnya valid
```

> `sso.greyfikal.web.id` ada di sertifikat Let's Encrypt `pld-vps` bersama
> `pld.`/`minio.`/`argocd.`, jadi **jangan** menyalakan `TLS_INSECURE_SKIP_VERIFY`.
> Pod menjangkaunya lewat override CoreDNS (`kube-system/coredns-custom`) yang
> memetakan nama itu ke IP host — nama host tetap utuh, jadi verifikasi TLS penuh
> tetap lulus.

### 3. Di **backoffice** (`NEXT_PUBLIC_APP_HUBNET_URL`)

```
https://sso.greyfikal.web.id/sso/oauth/authorize?client_id=0bc24bbf-4912-4621-aa00-361795f3e18e&redirect_uri=https%3A%2F%2Fpld.greyfikal.web.id%2Fhubnet%2Fsso&response_type=code&scope=&login_api=null
```

> Ini nilai build-time Next.js — perlu **rebuild** backoffice setelah diganti.

---

## Deploy tiruan ini

**Deployment aktif ada di VPS PLD**, sebagai Docker Compose di `/opt/pld-hubnet`
(bukan lewat `.github/workflows/deploy.yml`, yang masih menyasar host lama).

```bash
ssh aspd
cd /opt/pld-src && docker build -t localhost/hubnet-tiruan:<tag> ./pld-mitra-example
cd /opt/pld-hubnet   # sesuaikan tag di docker-compose.yml
docker compose up -d
docker compose exec hubnet php artisan migrate --force
docker compose exec hubnet php artisan hubnet:seed   # WAJIB — migrate tidak menyemai akun
```

Dua hal yang memakan waktu untuk ditemukan, jangan diulangi:

- **`Dockerfile` memakai `php:8.4`, bukan 8.3** yang tertulis di `require`
  composer.json. `composer.lock` sudah mengunci komponen Symfony 8 yang menuntut
  `php >=8.4.1`; build di 8.3 gagal dengan 17 konflik platform sekaligus.
- **`env` yang di-mount harus terbaca `www-data`** (`chown root:33`, `chmod 640`).
  Dengan mode 600 milik root, `php artisan` lewat `docker exec` tetap jalan
  (sebagai root) sementara SETIAP permintaan web gagal 500 dengan
  `MissingAppKeyException` — gejala yang menyesatkan karena CLI-nya sehat.
- **MariaDB, bukan SQLite.** Migrasi di sini menuliskan `collate 'utf8mb4_unicode_ci'`
  eksplisit; SQLite menolaknya dengan "no such collation sequence".

`hubnet:seed` idempoten (aman diulang) dan mencetak daftar akun + kata sandinya.

---

## Mengelola user & klien lewat panel operator

Setelah masuk sebagai operator (`/operator/masuk`; buat akun dengan
`php artisan pel:operator` bila belum ada), tersedia dua menu:

- **Hubnet: User** (`/admin/hubnet-users`) — CRUD identitas dummy. Buat/ubah/hapus
  akun uji tanpa bergantung pada seeder: atur tipe, NIP/NIK/unit, dan penanda
  aktif (hilangkan centang "Aktif" untuk menguji penolakan B5).
- **Hubnet: Klien** (`/admin/hubnet-clients`) — CRUD aplikasi yang boleh memakai
  SSO ini. `client_id`/`client_secret` dibangkitkan otomatis saat klien dibuat
  dan ditampilkan untuk disalin ke env aplikasi klien; ada aksi "bangkitkan ulang
  secret" dan tombol nonaktif (menolak klien tanpa menghapusnya).

Sumber klien kini **basis data** (bukan lagi hanya env). Klien dari env tetap
berfungsi sebagai **cadangan** dan otomatis muncul di panel setelah
`php artisan hubnet:seed` (baris "PLD (default dari env)").

## Akun contoh (di-seed)

Kata sandi semua akun: **`hubnet123`**. Radio di halaman login menentukan `type`.

| Radio | Username | Hasil di pld-dev |
|---|---|---|
| PEGAWAI KEMENHUB | `199608082022031008` | ✅ masuk (akun PERSON, unit DJPU → layak admin) |
| PEGAWAI KEMENHUB | `198701012010012002` | ⛔ ditolak — B5 (identitas nonaktif) |
| OSS | `1409210000868` | ✅ masuk (akun ORGANIZATION) |
| OSS | `tockhamdani@gmail.com` | ⛔ ditolak — K4 ("harus pakai NIB") |
| LAINNYA | `warga@gmail.com` | ⛔ ditolak — type 3 belum didukung PLD |

Tiga penolakan itu **bukan bug** — begitulah pld-user dirancang. Tiruan ini ada
justru supaya penolakan-penolakan itu bisa dilihat tanpa punya akun sungguhan.

---

## Batas & sifat keamanan (sudah ditegakkan + diuji)

- Authorization code & access token disimpan **sebagai hash**, **sekali pakai**
  (code), **berumur pendek**.
- `redirect_uri` dicocokkan **sama persis** dan **terikat** pada code — code yang
  ditukar ke tujuan lain ditolak.
- `client_secret` diverifikasi dengan `hash_equals`; salah → `invalid_client`.
- Config `client_id`/`secret` kosong = **tolak semua** (bukan "izinkan semua").
- Halaman login publik dibatasi laju 60/menit; token & userinfo 120/menit.

Uji: `tests/Feature/Hubnet/HubnetSsoFlowTest.php` (9 uji, alur penuh + semua sifat
di atas). Jalankan `php artisan test`.
