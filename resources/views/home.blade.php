@extends('layouts.app')

@section('title', 'Beranda — PEL')

@section('content')
    @if ($member)
        {{-- Cara masuk ditampilkan apa adanya, bukan diasumsikan.
             Sebelumnya panel ini selalu menulis "Masuk lewat SSO PLD" untuk setiap
             member yang login — termasuk yang masuk lewat form biasa. Saat menguji
             integrasi, itu membuat login langsung tampak seperti bukti SSO sudah
             bekerja, dan menyembunyikan justru hal yang sedang diuji. --}}
        @php $viaSso = session('login_via') === 'sso'; @endphp

        <div class="mb-5 rounded-xl border p-5 {{ $viaSso ? 'border-green-200 bg-green-50' : 'border-blue-200 bg-blue-50' }}">
            <p class="text-sm font-semibold {{ $viaSso ? 'text-green-900' : 'text-blue-900' }}">
                {{ $viaSso ? 'Masuk lewat SSO PLD' : 'Masuk langsung di aplikasi ini (bukan lewat PLD)' }}
            </p>
            <p class="mt-0.5 text-sm {{ $viaSso ? 'text-green-800' : 'text-blue-800' }}">
                {{ $member->name }}
                (<span class="font-mono text-xs">{{ $member->user_login }}</span>)
            </p>
            @unless ($viaSso)
                <p class="mt-2 text-xs leading-relaxed text-blue-700">
                    Halaman ini <strong>tidak</strong> membuktikan SSO berfungsi. Untuk mengujinya,
                    keluar dulu lalu tekan layanan ini dari portal PLD.
                </p>
            @endunless
        </div>

        <h1 class="mb-3 text-lg font-bold text-gray-800">Permohonan Anda</h1>

        @forelse ($applications as $application)
            <a href="{{ route('permohonan.show', $application->external_ref) }}"
               class="mb-3 block rounded-xl border border-gray-200 bg-white p-5 hover:border-blue-300">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-medium text-gray-800">{{ $application->label }}</p>
                        <p class="mt-0.5 font-mono text-xs text-gray-400">{{ $application->external_ref }}</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $application->category->badgeClass() }}">
                        {{ $application->category->label() }}
                    </span>
                </div>
                <p class="mt-2 text-sm text-gray-600">{{ $application->status_label }}</p>
                <p class="mt-1 text-xs text-gray-400">
                    Tahap {{ $application->current_stage }} dari {{ $application->total_stages }}
                </p>
            </a>
        @empty
            <p class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">
                Belum ada permohonan atas nama Anda.
            </p>
        @endforelse
    @else
        <div class="rounded-xl border border-gray-200 bg-white p-8">
            <h1 class="text-lg font-bold text-gray-800">PEL — Perizinan Elektronik</h1>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-gray-600">
                Aplikasi contoh yang bertindak sebagai <strong>mitra PLD</strong>. Ia mengimplementasikan
                keempat endpoint kontrak integrasi dan bisa dipakai untuk menguji SSO, tracking,
                notifikasi, dan <strong>provisioning akun</strong> ujung ke ujung tanpa menunggu proses
                perizinan sungguhan.
            </p>

            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                <div class="rounded-lg border border-gray-200 p-4">
                    <p class="font-mono text-xs font-semibold text-blue-700">POST /api/pld/auth</p>
                    <p class="mt-1 text-xs text-gray-500">API Auth URL — tukar user_login jadi token SSO.</p>
                </div>
                <div class="rounded-lg border border-gray-200 p-4">
                    <p class="font-mono text-xs font-semibold text-blue-700">POST /api/pld/user/validation</p>
                    <p class="mt-1 text-xs text-gray-500">API User Validation URL — cocokkan kredensial saat menautkan akun.</p>
                </div>
                <div class="rounded-lg border border-gray-200 p-4">
                    <p class="font-mono text-xs font-semibold text-blue-700">POST /api/pld/tracking</p>
                    <p class="mt-1 text-xs text-gray-500">API Tracking URL — laporkan proses member (Jalur A).</p>
                </div>
                <div class="rounded-lg border border-blue-200 bg-blue-50/40 p-4">
                    <p class="font-mono text-xs font-semibold text-blue-700">POST /api/pld/provisioning</p>
                    <p class="mt-1 text-xs text-gray-500">API Provisioning URL — buatkan akun, nonaktifkan, atau ganti peran.</p>
                </div>
            </div>

            {{-- Provisioning dijelaskan lebih panjang daripada tiga endpoint lain, dan itu
                 disengaja: ia satu-satunya yang MENULIS ke aplikasi ini atas perintah PLD.
                 Tiga lainnya hanya membaca atau menukar token. Orang yang mendaftarkan URL
                 ini di portal PLD perlu tahu persis apa yang akan terjadi pada datanya. --}}
            <div class="mt-5 rounded-xl border border-gray-200 bg-gray-50/60 p-5">
                <h2 class="text-sm font-bold text-gray-800">Provisioning akun — cara kerjanya</h2>
                <p class="mt-1.5 text-xs leading-relaxed text-gray-600">
                    PLD <strong>tidak</strong> memanggil endpoint ini saat permohonan disetujui, melainkan
                    saat member menekan &ldquo;Buka Aplikasi&rdquo; untuk <strong>pertama kali</strong>. Dengan
                    begitu yang lahir di sini hanya akun yang benar-benar dibuka orangnya — bukan ribuan
                    akun mati dari pemberian akses massal.
                </p>

                <p class="mt-3 text-xs font-semibold text-gray-700">Tiga aksi, satu alamat</p>
                <ul class="mt-1 space-y-1 text-xs leading-relaxed text-gray-600">
                    <li><span class="font-mono text-gray-800">createAccount</span> — buatkan akun beserta perannya.</li>
                    <li><span class="font-mono text-gray-800">setStatus</span> — nonaktifkan/aktifkan saat akses dicabut atau dipulihkan.</li>
                    <li><span class="font-mono text-gray-800">setRole</span> — <strong>ganti seluruh</strong> daftar peran; yang tak ikut dikirim dilepas.</li>
                </ul>

                <p class="mt-3 text-xs font-semibold text-gray-700">Empat jawaban <span class="font-mono">createAccount</span></p>
                <ul class="mt-1 space-y-1 text-xs leading-relaxed text-gray-600">
                    <li><span class="font-mono text-gray-800">CREATED</span> — akun baru dibuat, <span class="font-mono">userLogin</span> dikembalikan.</li>
                    <li><span class="font-mono text-gray-800">EXISTS_LINKED</span> — akun sudah ada <em>dan</em> emailnya pernah kami verifikasi.</li>
                    <li>
                        <span class="font-mono text-gray-800">EXISTS_UNVERIFIED</span> — akun sudah ada tetapi
                        kepemilikannya <strong>tak pernah dibuktikan</strong>. Kami menolak menautkannya, dan
                        <span class="font-mono">userLogin</span> sengaja tidak dikirim: mengirimkannya berarti
                        membocorkan identitas akun orang lain. Member diarahkan ke &ldquo;Tautkan Akun&rdquo;.
                    </li>
                    <li><span class="font-mono text-gray-800">ROLE_REJECTED</span> — ada kode peran yang tidak kami kenal.</li>
                </ul>

                <p class="mt-3 text-xs leading-relaxed text-gray-600">
                    Jawaban selalu <span class="font-mono">HTTP 200</span> selama permintaannya sah — hasilnya
                    dibedakan lewat field <span class="font-mono">status</span>, sama seperti
                    <span class="font-mono">is_valid</span> pada User Validation. Kunci
                    <span class="font-mono">api-key</span> yang salah dijawab
                    <span class="font-mono">400</span>, sama dengan tiga endpoint lainnya.
                </p>

                <p class="mt-3 text-xs leading-relaxed text-gray-600">
                    <span class="font-mono">requestId</span> adalah <strong>kunci idempotensi</strong>:
                    percobaan ulang dengan nilai yang sama dijawab persis sama tanpa dikerjakan lagi.
                    Tanpa itu, satu jawaban yang hilang di jaringan akan melahirkan akun kedua untuk
                    orang yang sama.
                </p>

                <p class="mt-3 text-xs leading-relaxed text-gray-600">
                    Peran yang dikirim PLD divalidasi terhadap daftar yang dikenal aplikasi ini:
                    @foreach ((array) config('pld.provisioning.roles', []) as $role)<span class="font-mono text-gray-800">{{ $role }}</span>@if (! $loop->last), @endif @endforeach.
                    Kode di luar daftar itu dijawab <span class="font-mono">ROLE_REJECTED</span> — peran
                    diperlakukan sebagai permintaan yang diperiksa, bukan perintah yang diikuti.
                </p>

                <p class="mt-3 text-xs leading-relaxed text-gray-500">
                    Akun yang kami buat <strong>tidak punya password yang bisa dipakai</strong>: masuknya
                    lewat SSO PLD. Password tidak pernah mengalir lewat PLD, dan tidak pernah kami
                    kembalikan di dalam jawaban.
                </p>

                <p class="mt-3 text-xs leading-relaxed text-gray-500">
                    Daftarkan di portal PLD sebagai <span class="font-mono">API Provisioning URL</span>:
                    <span class="font-mono text-gray-800">{{ url('/api/pld/provisioning') }}</span>
                </p>
            </div>

            <p class="mt-5 text-xs leading-relaxed text-gray-500">
                Anda belum masuk. Member mendarat di sini lewat
                <span class="font-mono">{{ route('sso.landing') }}?pld_auth=&lt;token&gt;</span>
                setelah menekan layanan ini di portal PLD — atau bisa
                <a href="{{ route('masuk') }}" class="text-blue-700 hover:underline">masuk langsung</a>
                dengan kredensial PEL, yaitu pasangan yang sama yang diverifikasi
                <span class="font-mono">API User Validation URL</span>.
            </p>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('masuk') }}"
                   class="inline-block rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    Masuk sebagai member
                </a>
                <a href="{{ route('operator.masuk') }}"
                   class="inline-block rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Panel operator
                </a>
            </div>
        </div>
    @endif
@endsection
