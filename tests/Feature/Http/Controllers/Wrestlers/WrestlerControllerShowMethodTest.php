<?php

declare(strict_types=1);

use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\User;
use App\Models\Wrestler;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->wrestler = Wrestler::factory()->create();
});

test('show returns a view', function () {
    actingAs(administrator())
        ->get(action([WrestlersController::class, 'show'], $this->wrestler))
        ->assertOk()
        ->assertViewIs('wrestlers.show')
        ->assertViewHas('wrestler', $this->wrestler);
});

test('a basic user can view their wrestler profile', function () {
    $wrestler = Wrestler::factory()->for($user = basicUser())->create();

    actingAs($user)
        ->get(action([WrestlersController::class, 'show'], $wrestler))
        ->assertOk()
        ->assertViewIs('wrestlers.show')
        ->assertViewHas('wrestler', $wrestler);
});

test('a basic user cannot view another users wrestler profile', function () {
    $wrestler = Wrestler::factory()->for(User::factory())->create();

    actingAs(basicUser())
        ->get(action([WrestlersController::class, 'show'], $wrestler))
        ->assertForbidden();
});

test('a guest cannot view a wrestler profile', function () {
    get(action([WrestlersController::class, 'show'], $this->wrestler))
        ->assertRedirect(route('login'));
});
