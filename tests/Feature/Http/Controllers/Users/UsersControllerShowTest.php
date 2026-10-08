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
    /**
     * @see UsersController::show()
     */
    test('show returns a view', function () {
        $user = User::factory()->create();

        actingAs(administrator())
            ->get(route('users.show', $user))
            ->assertOk()
            ->assertViewIs('users.show')
            ->assertViewHas('user', $user);
    });

    /**
     * @see UsersController::show()
     */
    test('show renders the users general information', function () {
        User::factory()->create();

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
        User::factory()->create();

        actingAs($user = basicUser())
            ->get(route('users.show', $user))
            ->assertForbidden();
    });

    /**
     * @see UsersController::show()
     */
    test('a basic user cannot view another users profile', function () {
        User::factory()->create();

        $otherUser = User::factory()->create();

        actingAs(basicUser())
            ->get(route('users.show', $otherUser))
            ->assertForbidden();
    });

    /**
     * @see UsersController::show()
     */
    test('a guest cannot view a user profile', function () {
        $user = User::factory()->create();

        get(route('users.show', $user))
            ->assertRedirect(route('login'));
    });

    /**
     * @see UsersController::show()
     */
    test('returns 404 when user does not exist', function () {
        User::factory()->create();

        actingAs(administrator())
            ->get(route('users.show', 999999))
            ->assertNotFound();
    });
});
