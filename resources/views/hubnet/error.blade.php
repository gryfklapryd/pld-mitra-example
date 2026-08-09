<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HUBNET — SSO</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-full place-items-center bg-slate-900 text-slate-100 antialiased">
    <div class="mx-4 max-w-md rounded-2xl bg-white p-8 text-slate-800 shadow-2xl">
        <p class="text-lg font-black tracking-tight text-slate-900">HUBNET <span class="text-xs font-normal text-slate-400">SSO (tiruan uji)</span></p>
        <div class="mt-4 rounded-lg border-l-4 border-red-500 bg-red-50 p-3 text-sm text-red-800">
            {{ $message }}
        </div>
        <p class="mt-4 text-xs leading-relaxed text-slate-500">
            Galat ini ditampilkan di sini, bukan dikirim balik ke aplikasi pemanggil —
            pengalihan hanya dilakukan ke alamat yang sudah terdaftar.
        </p>
    </div>
</body>
</html>
