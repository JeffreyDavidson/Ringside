<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;

uses(DatabaseMigrations::class);

test('login screen displays correctly', function () {
    $page = visit(route('login'));

    $page->assertSee('Sign in')
        ->assertElementPresent('input[name="email"]')
        ->assertElementPresent('input[name="password"]')
        ->assertElementPresent('button')
        ->assertSee('Email')
        ->assertSee('Password')
        ->assertAttribute('input[name="email"]', 'placeholder', 'email@email.com')
        ->assertAttribute('input[name="password"]', 'placeholder', 'Enter Password')
        ->assertNoJavascriptErrors();
});

test('user can authenticate successfully', function () {
    $user = User::factory()->create([
        'email' => 'test@ringside.test',
        'password' => 'password123',
    ]);

    $page = visit(route('login'));

    $page->type('email', $user->email)
        ->type('password', 'password123')
        ->press('Sign In')
        ->waitForRoute('dashboard')
        ->assertSee('Dashboard')
        ->assertNoJavascriptErrors();
});

test('authentication fails with invalid credentials', function () {
    $page = visit(route('login'));

    $page->type('email', 'nonexistent@example.com')
        ->type('password', 'wrongpassword')
        ->press('Sign In')
        ->waitForText('These credentials do not match our records')
        ->assertSee('These credentials do not match our records')
        ->assertRouteIs('login')
        ->assertNoJavascriptErrors();
});

test('login form validates required fields', function () {
    $page = visit(route('login'));

    $page->press('Sign In')
        ->waitForText('The email field is required')
        ->assertSee('The email field is required')
        ->assertSee('The password field is required')
        ->assertRouteIs('login')
        ->assertNoJavascriptErrors();
});

test('login form validates email format', function () {
    $page = visit(route('login'));

    $page->type('email', 'invalid-email-format')
        ->type('password', 'somepassword')
        ->press('Sign In')
        ->waitForText('The email field must be a valid email address')
        ->assertSee('The email field must be a valid email address')
        ->assertRouteIs('login')
        ->assertNoJavascriptErrors();
});

test('login form has proper accessibility', function () {
    $page = visit(route('login'));

    $page->assertAttribute('input[name="email"]', 'type', 'text')
        ->assertAttribute('input[name="password"]', 'type', 'password')
        ->assertElementPresent('label')
        ->assertNoJavascriptErrors();
});

test('login form keyboard navigation works', function () {
    $page = visit(route('login'));

    $page->click('input[name="email"]')
        ->assertFocused('input[name="email"]')
        ->key('Tab')
        ->assertFocused('input[name="password"]')
        ->key('Tab')
        ->assertFocused('button')
        ->assertNoJavascriptErrors();
});

test('remember me functionality works if present', function () {
    $user = User::factory()->create([
        'email' => 'remember@ringside.test',
        'password' => 'password123',
    ]);

    $page = visit(route('login'));

    $page->type('email', $user->email)
        ->type('password', 'password123');

    // Check if remember me checkbox exists
    if ($page->hasElement('input[name="remember"]')) {
        $page->check('remember');
    }

    $page->press('Sign In')
        ->waitForRoute('dashboard')
        ->assertSee('Dashboard')
        ->assertNoJavascriptErrors();
});

test('login form works on mobile viewports', function () {
    $user = User::factory()->create([
        'email' => 'mobile@ringside.test',
        'password' => 'password123',
    ]);

    $page = visit(route('login'))->mobile(); // Pest 4.0 mobile viewport

    $page->assertElementPresent('input[name="email"]')
        ->assertElementPresent('input[name="password"]')
        ->assertElementPresent('button')
        ->type('email', $user->email)
        ->type('password', 'password123')
        ->press('Sign In')
        ->waitForRoute('dashboard')
        ->assertSee('Dashboard')
        ->assertNoJavascriptErrors();
});

test('user can logout successfully', function () {
    $user = User::factory()->create([
        'email' => 'logout@ringside.test',
        'password' => 'password123',
    ]);

    // First login
    $page = visit(route('login'));

    $page->type('email', $user->email)
        ->type('password', 'password123')
        ->press('Sign In')
        ->waitForRoute('dashboard')
        ->assertSee('Dashboard')
        ->press('Log out')
        ->waitForText('Sign in')
        ->assertSee('Sign in')
        ->assertNoJavascriptErrors();

    // Verify we can't access protected pages
    $page = visit(route('dashboard'));

    $page->waitForRoute('login')
        ->assertSee('Sign in')
        ->assertNoJavascriptErrors();
});

test('authenticated users are redirected away from login page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $page = visit(route('login'));

    $page->waitForRoute('dashboard')
        ->assertSee('Dashboard')
        ->assertNoJavascriptErrors();
});

test('login form handles longer processing times', function () {
    $user = User::factory()->create([
        'email' => 'slow@ringside.test',
        'password' => 'password123',
    ]);

    $page = visit(route('login'));

    $page->type('email', $user->email)
        ->type('password', 'password123')
        ->press('Sign In')
        ->waitForRoute('dashboard', 10) // Allow more time for processing
        ->assertSee('Dashboard')
        ->assertNoJavascriptErrors();
});
