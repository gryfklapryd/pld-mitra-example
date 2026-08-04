<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Member;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password as promptPassword;
use function Laravel\Prompts\text;

/**
 * Membuat akun member layanan dari baris perintah.
 *
 * Aplikasi ini sengaja tidak punya halaman pendaftaran mandiri — di dunia nyata
 * member PEL lahir dari proses bisnisnya sendiri, bukan dari formulir daftar.
 * Perintah ini menggantikan proses itu untuk keperluan pengujian integrasi.
 *
 * `user_login` yang dibuat di sini adalah nilai yang HARUS diketik member saat
 * menautkan akunnya di portal PLD, dan nilai yang sama yang dikirim PLD pada
 * `userLogins[]` saat menyinkronkan tracking.
 */
final class CreateMemberCommand extends Command
{
    protected $signature = 'pel:member
                            {--user-login= : Identitas member di aplikasi ini (dikenal PLD)}
                            {--name= : Nama lengkap}
                            {--email= : Email (opsional)}
                            {--password= : Kata sandi (kosongkan agar ditanyakan tanpa tampil di layar)}';

    protected $description = 'Membuat akun member layanan untuk pengujian integrasi PLD';

    public function handle(): int
    {
        $userLogin = $this->option('user-login') ?: text(
            label: 'user_login (yang diketik member saat menautkan akun di PLD)',
            required: true,
        );

        $name = $this->option('name') ?: text(
            label: 'Nama lengkap',
            required: true,
        );

        $email = $this->option('email') ?: text(
            label: 'Email (boleh dikosongkan)',
            required: false,
        );

        $password = $this->option('password') ?: promptPassword(
            label: 'Kata sandi',
            required: true,
        );

        $validator = Validator::make(
            [
                'user_login' => $userLogin,
                'name' => $name,
                'email' => $email ?: null,
                'password' => $password,
            ],
            [
                'user_login' => ['required', 'string', 'max:100', 'unique:members,user_login'],
                'name' => ['required', 'string', 'max:150'],
                'email' => ['nullable', 'email', 'max:190'],
                'password' => ['required', 'string', 'min:8'],
            ],
            [
                'user_login.unique' => 'user_login itu sudah dipakai member lain.',
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        Member::query()->create([
            'user_login' => $userLogin,
            'name' => $name,
            'email' => $email ?: null,
            'password' => $password,
            'is_active' => true,
        ]);

        $this->components->info("Member {$userLogin} dibuat.");
        $this->line('');
        $this->line('  Di portal PLD, member menautkan akun dengan:');
        $this->line("    user_login : <fg=yellow>{$userLogin}</>");
        $this->line('    password   : (yang baru saja Anda tetapkan)');

        return self::SUCCESS;
    }
}
