<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password as promptPassword;
use function Laravel\Prompts\text;

/**
 * Membuat akun operator dari baris perintah.
 *
 * Ada karena seeder TIDAK boleh dijalankan di produksi: `DatabaseSeeder` memuat
 * kata sandi contoh yang tertulis di repositori ini dan terbaca siapa pun.
 * Tanpa perintah ini, server yang baru di-deploy (`migrate --force` saja, tanpa
 * seed) tidak punya satu pun akun yang bisa membuka panel operator.
 */
final class CreateOperatorCommand extends Command
{
    protected $signature = 'pel:operator
                            {--name= : Nama operator}
                            {--email= : Email untuk masuk}
                            {--password= : Kata sandi (kosongkan agar ditanyakan tanpa tampil di layar)}';

    protected $description = 'Membuat akun operator internal untuk panel /admin';

    public function handle(): int
    {
        $name = $this->option('name') ?: text(
            label: 'Nama operator',
            required: true,
        );

        $email = $this->option('email') ?: text(
            label: 'Email',
            required: true,
        );

        // Ditanyakan tersembunyi bila tidak dilewatkan sebagai opsi: kata sandi
        // yang diketik sebagai argumen akan tersimpan di riwayat shell dan terlihat
        // di daftar proses server.
        $password = $this->option('password') ?: promptPassword(
            label: 'Kata sandi',
            required: true,
        );

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:190', 'unique:users,email'],
                'password' => ['required', 'string', 'min:12'],
            ],
            [
                'email.unique' => 'Email itu sudah dipakai operator lain.',
                'password.min' => 'Kata sandi minimal 12 karakter — akun ini membuka panel yang bisa mengirim email atas nama layanan.',
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        $this->components->info("Operator {$email} dibuat. Masuk lewat /operator/masuk.");

        return self::SUCCESS;
    }
}
