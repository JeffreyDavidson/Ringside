<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->administrator()->count(2)->sequence(fn (Sequence $sequence) =>
            [
                'first_name' => ($sequence->index !== 0 ? Str::of(Number::spellOrdinal($sequence->index + 1). ' ')->title() : '').ucwords(Role::Administrator->value),
                'last_name' => 'User',
                'email' => Role::Administrator->value.($sequence->index ? Number::format($sequence->index + 1) : '').'@example.com',
                'password' => 'password',
                'status' => UserStatus::Active,
                'avatar_path' => '300-3.png',
                'phone_number' => fake()->unique()->numberBetween(0000000000, 9999999999),
            ]
        )->create();

        User::factory()->basicUser()->count(300)->sequence(fn (Sequence $sequence) =>
            [
                'first_name' => ($sequence->index !== 0 ? Str::of(Number::spellOrdinal($sequence->index + 1). ' ')->title() : '').ucwords(Role::Basic->value),
                'last_name' => 'User',
                'email' => Role::Basic->value.($sequence->index ? Number::format($sequence->index + 1) : '').'@example.com',
                'password' => 'password',
                'status' => UserStatus::Active,
                'avatar_path' => '300-3.png',
                'phone_number' => fake()->unique()->numberBetween(0000000000, 9999999999),
            ]
        )->create();
    }
}
