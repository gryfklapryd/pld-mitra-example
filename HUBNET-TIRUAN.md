# Hubnet TIRUAN — IdP palsu untuk uji SSO

Aplikasi ini, di samping perannya sebagai mitra hilir PLD, kini juga menyediakan
**tiruan Hubnet**: server SSO palsu yang meniru `hubnet.kemenhub.go.id` secara
**fungsional**, supaya login SSO di `pld-dev.ortala-djpu.my.id` bisa diuji langsung
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
1. Orang buka https://pld-dev.ortala-djpu.my.id/login → klik "SSO HUBNET"
2. Peramban lompat ke  {HUBNET_DOMAIN}/sso/oauth/authorize?client_id=…&redirect_uri=…&response_type=code
3. Halaman login TIRUAN → pilih salah satu akun contoh → Log In
4. TIRUAN terbitkan `code`, redirect balik ke  {BO_DOMAIN}/hubnet/sso?code=…&state=…
5. FE PLD → POST /api/sso-internal → pld-user  POST /user/api/v1/auth/hubnet
6. pld-user → POST {HUBNET_DOMAIN}/sso/oauth/token   → {token_type, access_token}
7. pld-user → GET  {HUBNET_DOMAIN}/sso/api/user      → {data_user:{…}}
8. pld-user provisioning → sesi PLD terbit → masuk.
```

`{HUBNET_DOMAIN}` = `https://202-155-132-181.nip.io` (deployment aplikasi ini).

---

## Nilai yang harus disetel (turnkey)

### 1. Di aplikasi ini (`.env` pada VPS — `/var/www/myapp/shared/.env`)

```dotenv
HUBNET_FAKE_CLIENT_ID=0bc24bbf-4912-4621-aa00-361795f3e18e
HUBNET_FAKE_CLIENT_SECRET=1d4274360762607e17e89a6cd453cdd2c6f66000be4bddbc
HUBNET_FAKE_REDIRECT_URIS=https://pld-dev.ortala-djpu.my.id/hubnet/sso
HUBNET_FAKE_CODE_TTL=120
HUBNET_FAKE_TOKEN_TTL=300
```

> Ganti `client_id`/`client_secret` dengan nilai Anda sendiri bila mau — yang
> penting **sama persis** dengan yang disetel di pld-user (butir 2).
> `HUBNET_FAKE_REDIRECT_URIS` harus memuat `BO_DOMAIN + "/hubnet/sso"` milik PLD.

### 2. Di **pld-user** (secret cluster / `secrets-templates/pld-user.env`)

```dotenv
HUBNET_DOMAIN=https://202-155-132-181.nip.io
HUBNET_CLIENT_ID=0bc24bbf-4912-4621-aa00-361795f3e18e
HUBNET_CLIENT_SECRET=1d4274360762607e17e89a6cd453cdd2c6f66000be4bddbc
BO_DOMAIN=https://pld-dev.ortala-djpu.my.id      # sudah terisi
TLS_INSECURE_SKIP_VERIFY=                          # BIARKAN KOSONG — sertifikat nip.io valid
```

> Sertifikat `202-155-132-181.nip.io` sah (Let's Encrypt), jadi **jangan**
> menyalakan `TLS_INSECURE_SKIP_VERIFY`.

### 3. Di **backoffice** (`NEXT_PUBLIC_APP_HUBNET_URL`)

```
https://202-155-132-181.nip.io/sso/oauth/authorize?client_id=0bc24bbf-4912-4621-aa00-361795f3e18e&redirect_uri=https%3A%2F%2Fpld-dev.ortala-djpu.my.id%2Fhubnet%2Fsso&response_type=code&scope=&login_api=null
```

> Ini nilai build-time Next.js — perlu **rebuild** backoffice setelah diganti.

---

## Deploy tiruan ini

Push ke `main` memicu deploy (lihat `.github/workflows/deploy.yml`). Sesudah deploy:

```bash
# di VPS
php artisan migrate --force     # sudah dijalankan pipeline; aman diulang
php artisan hubnet:seed         # WAJIB — migrate tidak menyemai akun
```

`hubnet:seed` idempoten (aman diulang) dan mencetak daftar akun + kata sandinya.

---

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
