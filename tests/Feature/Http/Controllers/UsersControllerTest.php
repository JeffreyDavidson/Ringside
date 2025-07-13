<?php

declare(strict_types=1);

use App\Http\Controllers\UsersController;
use App\Livewire\Users\Tables\UsersTable;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for UsersController.
 *
 * @see UsersController
 */
describe('index', function () {
    /**
     * @see UsersController::index()
     */
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action([UsersController::class, 'index']))
            ->assertOk()
            ->assertViewIs('users.index')
            ->assertSeeLivewire(UsersTable::class);
    });

    /**
     * @see UsersController::index()
     */
    test('a basic user cannot view Users index page', function () {
        actingAs(basicUser())
            ->get(action([UsersController::class, 'index']))
            ->assertForbidden();
    });

    /**
     * @see UsersController::index()
     */
    test('a guest cannot view users index page', function () {
        get(action([UsersController::class, 'index']))
            ->assertRedirect(route('login'));
    });
});

describe('show', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
    });

    /**
     * @see UsersController::show()
     */
    test('show returns a view', function () {
        actingAs(administrator())
            ->get(action([UsersController::class, 'show'], $this->user))
            ->assertOk()
            ->assertViewIs('users.show')
            ->assertViewHas('user', $this->user);
    });

    /**
     * @see UsersController::show()
     */
    test('a basic user can view their user profile', function () {
        actingAs($user = basicUser())
            ->get(action([UsersController::class, 'show'], $user))
            ->assertForbidden();
    });

    /**
     * @see UsersController::show()
     */
    test('a basic user cannot view another users profile', function () {
        $otherUser = User::factory()->create();

        actingAs(basicUser())
            ->get(action([UsersController::class, 'show'], $otherUser))
            ->assertForbidden();
    });

    /**
     * @see UsersController::show()
     */
    test('a guest cannot view a user profile', function () {
        get(action([UsersController::class, 'show'], $this->user))
            ->assertRedirect(route('login'));
    });

    /**
     * @see UsersController::show()
     */
    test('returns 404 when user does not exist', function () {
        actingAs(administrator())
            ->get(action([UsersController::class, 'show'], 999999))
            ->assertNotFound();
    });
});
