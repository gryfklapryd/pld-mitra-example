<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * CRUD member layanan & operator internal dari panel — pengganti pel:member /
 * pel:operator. Menegakkan juga dua penjaga penghapusan operator.
 */
final class MemberOperatorCrudTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function operator_membuat_member(): void
    {
        $this->actingAs($this->operator());

        $this->post(route('admin.members.store'), [
            'user_login' => 'john_doe',
            'name' => 'John Doe',
            'email' => 'john@example.test',
            'password' => 'rahasia123',
            'is_active' => '1',
        ])->assertRedirect(route('admin.members.index'));

        $this->assertDatabaseHas('members', ['user_login' => 'john_doe', 'is_active' => true]);
        $this->assertTrue(Hash::check('rahasia123', Member::query()->where('user_login', 'john_doe')->value('password')));
    }

    #[Test]
    public function ubah_member_tanpa_password_tidak_mengganti_password_lama(): void
    {
        $this->actingAs($this->operator());
        $member = Member::query()->create([
            'user_login' => 'siti', 'name' => 'Siti', 'email' => null,
            'password' => 'lama12345', 'is_active' => true,
        ]);

        $this->put(route('admin.members.update', $member), [
            'user_login' => 'siti', 'name' => 'Siti Baru', 'password' => '', 'is_active' => '1',
        ])->assertRedirect(route('admin.members.index'));

        $member->refresh();
        $this->assertSame('Siti Baru', $member->name);
        $this->assertTrue(Hash::check('lama12345', $member->password));
    }

    #[Test]
    public function operator_membuat_operator_lain(): void
    {
        $this->actingAs($this->operator());

        $this->post(route('admin.operators.store'), [
            'name' => 'Operator Dua',
            'email' => 'dua@pel.test',
            'password' => 'kata-sandi-panjang',
        ])->assertRedirect(route('admin.operators.index'));

        $this->assertDatabaseHas('users', ['email' => 'dua@pel.test']);
    }

    #[Test]
    public function password_operator_kurang_dari_12_ditolak(): void
    {
        $this->actingAs($this->operator());

        $this->post(route('admin.operators.store'), [
            'name' => 'Pendek', 'email' => 'pendek@pel.test', 'password' => 'pendek',
        ])->assertSessionHasErrors('password');
    }

    #[Test]
    public function tidak_bisa_menghapus_akun_operator_sendiri(): void
    {
        $me = $this->operator();
        $this->actingAs($me);

        $this->delete(route('admin.operators.destroy', $me))->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $me->id]);
    }

    #[Test]
    public function bisa_menghapus_operator_lain(): void
    {
        $me = $this->operator();
        $other = User::query()->create(['name' => 'Lain', 'email' => 'lain@pel.test', 'password' => 'kata-sandi-panjang']);
        $this->actingAs($me);

        $this->delete(route('admin.operators.destroy', $other))->assertRedirect(route('admin.operators.index'));
        $this->assertDatabaseMissing('users', ['id' => $other->id]);
    }

    private function operator(): User
    {
        return User::query()->create([
            'name' => 'Operator Uji',
            'email' => 'op-'.bin2hex(random_bytes(4)).'@pel.test',
            'password' => 'kata-sandi-panjang',
        ]);
    }
}
