<?php

declare(strict_types=1);

use App\Http\Controllers\StablesController;
use App\Models\Stable;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->stable = Stable::factory()->create();
});

test('show returns a view', function () {
    actingAs(administrator())
        ->get(action([StablesController::class, 'show'], $this->stable))
        ->assertViewIs('stables.show')
        ->assertViewHas('stable', $this->stable);
});

test('a basic user can view their stable profile', function () {
    $stable = Stable::factory()->for($user = basicUser())->create();

    actingAs($user)
        ->get(action([StablesController::class, 'show'], $stable))
        ->assertOk();
});

test('a basic user cannot view another users stable profile', function () {
    $stable = Stable::factory()->for(User::factory())->create();

    actingAs(basicUser())
        ->get(action([StablesController::class, 'show'], $stable))
        ->assertForbidden();
});

test('a guest cannot view a stable profile', function () {
    get(action([StablesController::class, 'show'], $this->stable))
        ->assertRedirect(route('login'));
});
