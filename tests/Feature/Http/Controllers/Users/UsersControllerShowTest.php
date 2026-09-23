<?php

declare(strict_types=1);

use App\Http\Controllers\Users\UsersController;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Users Controller.
 *
 * @see UsersController
 */
describe('Users Controller', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
    });

    /**
     * @see UsersController::show()
     */
    test('show returns a view', function () {
        actingAs(administrator())
            ->get(route('users.show', $this->user))
            ->assertOk()
            ->assertViewIs('users.show')
            ->assertViewHas('user', $this->user);
    });

    /**
     * @see UsersController::show()
     */
    test('show renders the users general information', function () {
        $user = User::factory()->administrator()->create([
            'first_name' => 'Casey',
            'last_name' => 'Ringside',
            'email' => 'casey@example.test',
            'phone_number' => '2125550198',
            'email_verified_at' => now(),
        ]);

        actingAs(administrator())
            ->get(route('users.show', $user))
            ->assertOk()
            ->assertSee('General Info')
            ->assertSee('Casey Ringside')
            ->assertSee('casey@example.test')
            ->assertSee('(212) 555-0198')
            ->assertSee('Administrator')
            ->assertSee('Unverified')
            ->assertSee('Verified');
    });

    /**
     * @see UsersController::show()
     */
    test('a basic user can view their user profile', function () {
        actingAs($user = basicUser())
            ->get(route('users.show', $user))
            ->assertForbidden();
    });

    /**
     * @see UsersController::show()
     */
    test('a basic user cannot view another users profile', function () {
        $otherUser = User::factory()->create();

        actingAs(basicUser())
            ->get(route('users.show', $otherUser))
            ->assertForbidden();
    });

    /**
     * @see UsersController::show()
     */
    test('a guest cannot view a user profile', function () {
        get(route('users.show', $this->user))
            ->assertRedirect(route('login'));
    });

    /**
     * @see UsersController::show()
     */
    test('returns 404 when user does not exist', function () {
        actingAs(administrator())
            ->get(route('users.show', 999999))
            ->assertNotFound();
    });
});
