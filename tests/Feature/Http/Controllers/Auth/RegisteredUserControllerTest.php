<?php

declare(strict_types=1);

use App\Enums\Users\Role;
use App\Models\Users\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\assertAuthenticatedAs;

test('registration screen can be rendered', function () {
    $this->get(route('register'))
        ->assertSuccessful();
});

test('a user can register with their account details', function () {
    $this->post(route('register'), [
        'first_name' => 'Jeffrey',
        'last_name' => 'Davidson',
        'email' => 'jeffrey@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard'));

    $user = User::query()->where('email', 'jeffrey@example.com')->firstOrFail();

    expect($user)
        ->first_name->toBe('Jeffrey')
        ->last_name->toBe('Davidson')
        ->role->toBe(Role::Basic)
        ->and(Hash::check('password', $user->password))->toBeTrue();

    assertAuthenticatedAs($user);
});

test('registration requires valid account details', function () {
    $this->from(route('register'))
        ->post(route('register'), [
            'first_name' => '',
            'last_name' => '',
            'email' => 'not-an-email',
            'password' => 'secret',
            'password_confirmation' => 'different-secret',
        ])
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors(['first_name', 'last_name', 'email', 'password'])
        ->assertSessionHasInput('email')
        ->assertSessionMissing('_old_input.password')
        ->assertSessionMissing('_old_input.password_confirmation');
});
