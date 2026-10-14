<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Http\Controllers\Users\UsersController;
use App\Livewire\Users\Tables\Main;
use App\Models\Promotions\Promotion;
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
        // Arrange
        $user = User::factory()->create();

        actingAs(administrator());

        // Act
        $response = get(route('users.show', $user));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('users.show')
            ->assertViewHas('user', $user);
    });

    /**
     * @see UsersController::show()
     */
    test('show renders the users general information', function () {
        // Arrange
        User::factory()->create();

        $user = User::factory()->administrator()->create([
            'first_name' => 'Casey',
            'last_name' => 'Ringside',
            'email' => 'casey@example.test',
            'phone_number' => '2125550198',
            'email_verified_at' => now(),
        ]);

        actingAs(administrator());

        // Act
        $response = get(route('users.show', $user));

        // Assert
        $response
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
    test('a user without a promotion membership cannot view their user profile', function () {
        // Arrange
        User::factory()->create();

        actingAs($user = basicUser());

        // Act
        $response = get(route('users.show', $user));

        // Assert
        $response
            ->assertForbidden();
    });

    /**
     * @see UsersController::show()
     */
    test('a user without a promotion membership cannot view another users profile', function () {
        // Arrange
        User::factory()->create();

        $otherUser = User::factory()->create();

        actingAs(basicUser());

        // Act
        $response = get(route('users.show', $otherUser));

        // Assert
        $response
            ->assertForbidden();
    });

    /**
     * @see UsersController::show()
     */
    test('a guest cannot view a user profile', function () {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = get(route('users.show', $user));

        // Assert
        $response
            ->assertRedirect(route('login'));
    });

    /**
     * @see UsersController::show()
     */
    test('returns 404 when user does not exist', function () {
        // Arrange
        User::factory()->create();

        actingAs(administrator());

        // Act
        $response = get(route('users.show', 999999));

        // Assert
        $response
            ->assertNotFound();
    });
});

test('an administrator receives the users index view and table', function (): void {
    // Arrange
    actingAs(administrator());

    // Act
    $response = get(route('users.index'));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('users.index')
        ->assertSeeHtml('aria-label="Content workspace"')
        ->assertSeeLivewire(Main::class);
});

test('a guest cannot open the users index', function (): void {
    // Act
    $response = get(route('users.index'));

    // Assert
    $response->assertRedirect(route('login'));
});

test('a user without membership cannot open the users index', function (): void {
    // Arrange
    actingAs(basicUser());

    // Act
    $response = get(route('users.index'));

    // Assert
    $response
        ->assertForbidden()
        ->assertViewIs('promotions.no-membership');
});

test('an active member cannot open platform user management', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);
    actingAs($user);

    // Act
    $response = get(route('users.index'));
    $profileResponse = get(route('users.show', $user));

    // Assert
    $response
        ->assertForbidden()
        ->assertDontSee('General Info');
    $profileResponse
        ->assertForbidden()
        ->assertDontSee($user->email);
});
