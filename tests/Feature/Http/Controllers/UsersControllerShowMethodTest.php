<?php

declare(strict_types=1);

use App\Http\Controllers\UsersController;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('show returns a view', function () {
    actingAs(administrator())
        ->get(action([UsersController::class, 'show'], $this->user))
        ->assertOk()
        ->assertViewIs('users.show')
        ->assertViewHas('user', $this->user);
});

test('a basic user can view their user profile', function () {
    actingAs($user = basicUser())
        ->get(action([UsersController::class, 'show'], $user))
        ->assertOk()
        ->assertViewIs('users.show')
        ->assertViewHas('user', $user);
});

test('a basic user cannot view another users user profile', function () {
    actingAs(basicUser())
        ->get(action([UsersController::class, 'show'], $this->user))
        ->assertForbidden();
});

test('a guest cannot view a user profile', function () {
    get(action([UsersController::class, 'show'], $this->user))
        ->assertRedirect(route('login'));
});
