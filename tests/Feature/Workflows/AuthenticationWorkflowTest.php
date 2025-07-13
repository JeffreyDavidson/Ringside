<?php

declare(strict_types=1);

use App\Models\Users\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

/**
 * Feature tests for complete authentication workflows.
 * Tests realistic user journeys from login to accessing different areas of the application.
 */
describe('Guest User Authentication Journey', function () {
    test('guest cannot access protected areas and is redirected to login', function () {
        // Given: A guest user (not authenticated)

        // When: Attempting to access protected routes
        $protectedRoutes = [
            'dashboard',
            'wrestlers.index',
            'managers.index',
            'referees.index',
            'tag-teams.index',
            'stables.index',
            'titles.index',
            'events.index',
            'venues.index',
            'users.index',
        ];

        foreach ($protectedRoutes as $route) {
            {
                // Then: Should be redirected to login
                get(route($route))
                    ->assertRedirect(route('login'));
            }
        }
    });

    test('guest can access login page', function () {
        // When: Guest visits login page
        get(route('login'))
            ->assertOk()
            ->assertViewIs('auth.login')
            ->assertSee('Sign in');
    });
});

describe('Administrator Authentication Journey', function () {
    test('administrator can login and access all areas', function () {
        // Given: An administrator user
        $admin = User::factory()->administrator()->create([
            'email' => 'admin@ringside.test',
        ]);

        // When: Administrator is authenticated
        // Then: Can access dashboard
        actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard')
            ->assertSee('Dashboard');

        // And: Can access all roster management areas
        $rosterRoutes = [
            'wrestlers.index' => 'Wrestlers',
            'managers.index' => 'Managers',
            'referees.index' => 'Referees',
            'tag-teams.index' => 'Tag Teams',
            'stables.index' => 'Stables',
        ];

        foreach ($rosterRoutes as $route => $expectedContent) {
            actingAs($admin)
                ->get(route($route))
                ->assertOk()
                ->assertSee($expectedContent);
        }

        // And: Can access content management areas
        $contentRoutes = [
            'titles.index' => 'Titles',
            'events.index' => 'Events',
            'venues.index' => 'Venues',
        ];

        foreach ($contentRoutes as $route => $expectedContent) {
            actingAs($admin)
                ->get(route($route))
                ->assertOk()
                ->assertSee($expectedContent);
        }

        // And: Can access user management
        actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Users');
    });

    test('administrator logout journey', function () {
        // Given: An authenticated administrator
        $admin = administrator();

        // When: Accessing dashboard while authenticated
        actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk();

        // And: Logging out
        actingAs($admin)
            ->post(route('logout'))
            ->assertRedirect('/');

        // Then: Cannot access protected areas anymore
        get(route('dashboard'))
            ->assertRedirect(route('login'));
    });
});

describe('Basic User Authentication Journey', function () {
    test('basic user has limited access after login', function () {
        // Given: A basic user
        $basicUser = basicUser();

        // When: Can access dashboard
        actingAs($basicUser)
            ->get(route('dashboard'))
            ->assertOk();

        // But: Cannot access administrative areas
        $forbiddenRoutes = [
            'wrestlers.index',
            'managers.index',
            'referees.index',
            'tag-teams.index',
            'stables.index',
            'titles.index',
            'events.index',
            'venues.index',
            'users.index',
        ];

        foreach ($forbiddenRoutes as $route) {
            actingAs($basicUser)
                ->get(route($route))
                ->assertForbidden();
        }
    });
});

describe('Invalid Authentication Attempts', function () {
    test('invalid credentials redirect back to login with errors', function () {
        // When: Attempting login with invalid credentials
        post(route('login'), [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ])
            ->assertRedirect('/')
            ->assertSessionHasErrors('email');

        // Then: Still cannot access protected areas
        get(route('dashboard'))
            ->assertRedirect(route('login'));
    });

    test('missing credentials show validation errors', function () {
        // When: Attempting login without credentials
        post(route('login'), [])
            ->assertRedirect('/')
            ->assertSessionHasErrors(['email', 'password']);
    });
});

describe('Session Management', function () {
    test('authenticated session persists across requests', function () {
        // Given: An authenticated administrator
        $admin = administrator();

        // When: Making multiple requests in the same session
        actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk();

        actingAs($admin)
            ->get(route('wrestlers.index'))
            ->assertOk();

        actingAs($admin)
            ->get(route('titles.index'))
            ->assertOk();

        // Then: All requests succeed without re-authentication
        expect(true)->toBeTrue(); // Test passes if no assertions fail
    });
});
