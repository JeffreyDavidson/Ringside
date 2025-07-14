<?php

declare(strict_types=1);

use App\Http\Controllers\Users\IndexController;
use App\Http\Controllers\Users\ShowController;
use App\Livewire\Users\Tables\UsersTable;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Users Controllers.
 *
 * @see IndexController
 * @see ShowController
 */
describe('index', function () {
    /**
     * @see IndexController::__invoke()
     */
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action(IndexController::class))
            ->assertOk()
            ->assertViewIs('users.index')
            ->assertSeeLivewire(UsersTable::class);
    });

    /**
     * @see IndexController::__invoke()
     */
    test('a basic user cannot view Users index page', function () {
        actingAs(basicUser())
            ->get(action(IndexController::class))
            ->assertForbidden();
    });

    /**
     * @see IndexController::__invoke()
     */
    test('a guest cannot view users index page', function () {
        get(action(IndexController::class))
            ->assertRedirect(route('login'));
    });
});

describe('show', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
    });

    /**
     * @see ShowController::__invoke()
     */
    test('show returns a view', function () {
        actingAs(administrator())
            ->get(action(ShowController::class, $this->user))
            ->assertOk()
            ->assertViewIs('users.show')
            ->assertViewHas('user', $this->user);
    });

    /**
     * @see ShowController::__invoke()
     */
    test('a basic user can view their user profile', function () {
        actingAs($user = basicUser())
            ->get(action(ShowController::class, $user))
            ->assertForbidden();
    });

    /**
     * @see ShowController::__invoke()
     */
    test('a basic user cannot view another users profile', function () {
        $otherUser = User::factory()->create();

        actingAs(basicUser())
            ->get(action(ShowController::class, $otherUser))
            ->assertForbidden();
    });

    /**
     * @see ShowController::__invoke()
     */
    test('a guest cannot view a user profile', function () {
        get(action(ShowController::class, $this->user))
            ->assertRedirect(route('login'));
    });

    /**
     * @see ShowController::__invoke()
     */
    test('returns 404 when user does not exist', function () {
        actingAs(administrator())
            ->get(action(ShowController::class, 999999))
            ->assertNotFound();
    });
});
