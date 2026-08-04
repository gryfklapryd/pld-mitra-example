<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Member>
 */
final class MemberFactory extends Factory
{
    protected $model = Member::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $login = Str::lower($this->faker->unique()->userName());

        return [
            'user_login' => $login,
            'name' => $this->faker->name(),
            'email' => $login.'@example.go.id',
            'password' => 'rahasia123',
            'is_active' => true,
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
