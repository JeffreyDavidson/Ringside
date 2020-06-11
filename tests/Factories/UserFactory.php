<?php

namespace Tests\Factories;

use App\Enums\Role;
use App\Models\User;
use Christophrumpel\LaravelFactoriesReloaded\BaseFactory;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

class UserFactory extends BaseFactory
{
    protected string $modelClass = User::class;

    public function create(array $extra = []): User
    {
        return parent::build($extra);
    }

    public function make(array $extra = []): User
    {
        return parent::build($extra, 'make');
    }

    public function getDefaults(Faker $faker): array
    {
        return [
            'first_name' => $faker->firstName,
            'last_name' => $faker->lastName,
            'email' => $faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', // secret
            'remember_token' => Str::random(10),
            'role' => Role::ADMINISTRATOR,
        ];

    }

    public function superAdministrator(): UserFactory
    {
        return tap(clone $this)->overwriteDefaults([
           'role' => Role::SUPER_ADMINISTRATOR,
        ]);
    }

    public function administrator(): UserFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'role' => Role::ADMINISTRATOR,
        ]);
    }

    public function basicUser(): UserFactory
    {
        return tap(clone $this)->overwriteDefaults([
            'role' => Role::BASIC,
        ]);
    }

    public function withRole($role)
    {
        return tap(clone $this)->overwriteDefaults([
            'role' => $role,
        ]);
    }
}

