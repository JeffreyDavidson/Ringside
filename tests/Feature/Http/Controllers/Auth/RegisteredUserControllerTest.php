<?php

declare(strict_types=1);

test('registration screen can be rendered', function () {
    $this->get(route('register'))
        ->assertSuccessful();
});

test('registration requires valid account details', function () {
    $this->from(route('register'))
        ->post(route('register'), [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'secret',
            'password_confirmation' => 'different-secret',
        ])
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors(['name', 'email', 'password'])
        ->assertSessionHasInput('email')
        ->assertSessionMissing('_old_input.password')
        ->assertSessionMissing('_old_input.password_confirmation');
});
