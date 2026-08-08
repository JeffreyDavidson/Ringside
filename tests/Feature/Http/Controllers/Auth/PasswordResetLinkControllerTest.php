<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

test('password reset link screen can be rendered', function () {
    $this->get(route('password.request'))
        ->assertSuccessful();
});

test('password reset link requires a valid email address', function () {
    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => 'not-an-email'])
        ->assertRedirect(route('password.request'))
        ->assertSessionHasErrors('email')
        ->assertSessionHasInput('email');
});

test('password reset link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertSessionHasNoErrors();

    Notification::assertSentTo($user, ResetPassword::class);
});
