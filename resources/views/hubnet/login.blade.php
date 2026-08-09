<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HUBNET — Kemenhub | SSO (Tiruan Uji)</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-900 text-slate-100 antialiased">

<div class="min-h-screen w-full bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <div class="mx-auto flex min-h-screen max-w-6xl flex-col items-stretch gap-6 px-4 py-8 lg:flex-row lg:items-center">

        {{-- Panel kiri: identitas HUBNET (pendekatan fungsional, bukan salinan aset asli) --}}
        <section class="flex flex-1 flex-col justify-center rounded-2xl border border-white/5 bg-white/5 p-8 lg:p-12">
            <div class="flex items-center gap-3">
                <div class="grid h-12 w-12 place-items-center rounded-lg bg-white/10 text-xl font-black tracking-tight">H</div>
                <div>
                    <p class="text-2xl font-black tracking-tight">HUBNET</p>
                    <p class="text-[11px] uppercase tracking-[0.2em] text-slate-300">Sistem Penghubung Layanan Transportasi</p>
                </div>
            </div>
            <p class="mt-6 max-w-md text-sm leading-relaxed text-slate-300">
                Kementerian Perhubungan — Single Sign-On. Halaman ini adalah
                <span class="font-semibold text-amber-300">tiruan untuk pengujian integrasi</span>,
                bukan Hubnet sungguhan, dan hanya menerima akun contoh yang di-seed.
            </p>
            <div class="mt-8 flex items-center gap-4 text-[11px] uppercase tracking-widest text-slate-400">
                <span>DIGIPORTASI</span><span>·</span><span>SPBE</span><span>·</span><span>Pusdatin</span>
            </div>
        </section>

        {{-- Panel kanan: kartu login --}}
        <section class="w-full lg:max-w-md">
            <div class="rounded-2xl bg-white p-8 text-slate-800 shadow-2xl">
                <h1 class="text-2xl font-bold text-slate-900">Log In</h1>
                <p class="mt-0.5 text-sm text-slate-500">Single Sign-On (SSO)</p>

                @if ($errors->any())
                    <div class="mt-4 rounded-lg border-l-4 border-red-500 bg-red-50 p-3 text-sm text-red-800">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('hubnet.authorize.login') }}" class="mt-6 space-y-4">
                    @csrf
                    <input type="hidden" name="client_id" value="{{ $clientId }}">
                    <input type="hidden" name="redirect_uri" value="{{ $redirectUri }}">
                    <input type="hidden" name="state" value="{{ $state }}">

                    <div>
                        <label for="username" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Username</label>
                        <input id="username" name="username" required autofocus autocomplete="off"
                               value="{{ old('username') }}" placeholder="Masukan Username"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="password" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Password</label>
                        <input id="password" name="password" type="password" required autocomplete="off"
                               placeholder="Masukan Password"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>

                    <div class="flex flex-wrap items-center gap-4 pt-1 text-sm text-slate-700">
                        @foreach ($types as $type)
                            <label class="flex items-center gap-1.5">
                                <input type="radio" name="type" value="{{ $type->value }}"
                                       @checked((int) old('type', \App\Enums\HubnetUserType::Pegawai->value) === $type->value)
                                       class="text-blue-600 focus:ring-blue-500">
                                <span>{{ $type->radioLabel() }}</span>
                            </label>
                        @endforeach
                    </div>

                    <button type="submit"
                            class="mt-2 w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                        Log In
                    </button>
                </form>

                <div class="mt-6 text-center text-[11px] leading-relaxed text-slate-400">
                    <p>Pusat Data dan Teknologi Informasi</p>
                    <p>Kementerian Perhubungan</p>
                    <p class="mt-1">{{ $version }}</p>
                </div>
            </div>

            {{-- Pembantu uji: akun dummy yang di-seed. Klik untuk mengisi form. --}}
            <div class="mt-4 rounded-2xl border border-white/10 bg-white/5 p-4 text-sm text-slate-200">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-amber-300">Akun contoh (lingkungan uji)</p>
                <p class="mb-3 text-xs text-slate-400">Kata sandi semua akun: <span class="font-mono text-slate-200">{{ $demoPassword }}</span>. Klik satu baris untuk mengisi otomatis.</p>
                <ul class="space-y-1.5">
                    @foreach ($accounts as $acc)
                        <li>
                            <button type="button"
                                    class="js-fill w-full rounded-lg bg-white/5 px-3 py-2 text-left hover:bg-white/10"
                                    data-username="{{ $acc['username'] }}" data-type="{{ $acc['type'] }}">
                                <span class="inline-block rounded bg-white/10 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide">{{ $acc['typeLabel'] }}</span>
                                <span class="ml-1 font-mono text-xs text-slate-100">{{ $acc['username'] }}</span>
                                <span class="mt-0.5 block text-[11px] text-slate-400">{{ $acc['note'] }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    </div>
</div>

<script>
    document.querySelectorAll('.js-fill').forEach(function (el) {
        el.addEventListener('click', function () {
            document.getElementById('username').value = el.dataset.username;
            document.getElementById('password').value = @json($demoPassword);
            var radio = document.querySelector('input[name="type"][value="' + el.dataset.type + '"]');
            if (radio) radio.checked = true;
        });
    });
</script>

</body>
</html>
