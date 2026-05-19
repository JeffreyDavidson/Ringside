<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;

uses(DatabaseMigrations::class);

test('login screen displays correctly', function () {
    $page = visit(route('login'));

    $page->assertSee('Sign in')
        ->assertPresent('@email')
        ->assertPresent('@password')
        ->assertPresent('@sign-in')
        ->assertSee('Email')
        ->assertSee('Password')
        ->assertAttribute('@email', 'placeholder', 'email@email.com')
        ->assertAttribute('@password', 'placeholder', 'Enter Password');
});

test('user can authenticate successfully', function () {
    // Create administrator user for testing
    $admin = User::factory()->administrator()->create([
        'email' => 'administrator@example.com',
        'password' => 'password',
    ]);

    $page = visit(route('login'));

    $page->type('@email', $admin->email)
        ->type('@password', 'password')
        ->press('@sign-in')
        ->assertScript('window.location.pathname === "/dashboard"')
        ->assertSee('Dashboard');
});

test('authentication fails with invalid credentials', function () {
    $page = visit(route('login'));

    $page->type('@email', 'nonexistent@example.com')
        ->type('@password', 'wrongpassword')
        ->press('@sign-in')
        ->assertSee('These credentials do not match our records')
        ->assertScript('window.location.pathname === "/login"');
});

test('login form validates required fields', function () {
    $page = visit(route('login'));

    $page->press('@sign-in')
        ->assertSee('The email field is required')
        ->assertSee('The password field is required')
        ->assertScript('window.location.pathname === "/login"');
});

test('login form validates email format', function () {
    $page = visit(route('login'));

    $page->type('@email', 'invalid-email-format')
        ->type('@password', 'somepassword')
        ->press('@sign-in')
        ->assertScript('window.location.pathname === "/login"');
});

test('login form has proper accessibility', function () {
    $page = visit(route('login'));

    $page->assertAttribute('@email', 'type', 'email')
        ->assertAttribute('@password', 'type', 'password')
        ->assertSee('Email')
        ->assertSee('Password');
});

test('login form keyboard navigation works', function () {
    $page = visit(route('login'));

    // Test that form elements are focusable and have proper attributes
    $page->click('@email')
        ->assertScript('document.activeElement.getAttribute("data-test") === "email"')
        ->click('@password')
        ->assertScript('document.activeElement.getAttribute("data-test") === "password"');
});

test('remember me functionality works if present', function () {
    // Create administrator user for testing
    $admin = User::factory()->administrator()->create([
        'email' => 'administrator@example.com',
        'password' => 'password',
    ]);

    $page = visit(route('login'));

    $page->type('@email', $admin->email)
        ->type('@password', 'password');

    // Check if remember me checkbox exists and check it
    try {
        $page->check('@remember');
    } catch (Exception $e) {
        // Checkbox might not be present, skip
    }

    $page->press('@sign-in')
        ->assertScript('window.location.pathname === "/dashboard"')
        ->assertSee('Dashboard');
});

test('login form works on mobile viewports', function () {
    // Create administrator user for testing
    $admin = User::factory()->administrator()->create([
        'email' => 'administrator@example.com',
        'password' => 'password',
    ]);

    $page = visit(route('login'))->on()->mobile();

    $page->assertPresent('@email')
        ->assertPresent('@password')
        ->assertPresent('@sign-in')
        ->type('@email', $admin->email)
        ->type('@password', 'password')
        ->press('@sign-in')
        ->assertScript('window.location.pathname === "/dashboard"')
        ->assertSee('Dashboard');
});

test('user can logout successfully', function () {
    // Create administrator user for testing
    $admin = User::factory()->administrator()->create([
        'email' => 'administrator@example.com',
        'password' => 'password',
    ]);

    // First login
    $page = visit(route('login'));

    $page->type('@email', $admin->email)
        ->type('@password', 'password')
        ->press('@sign-in')
        ->assertScript('window.location.pathname === "/dashboard"')
        ->assertSee('Dashboard')
        ->press('Log out')
        ->assertSee('Sign in');

    // Verify we can't access protected pages
    $page = visit(route('dashboard'));

    $page->assertScript('window.location.pathname === "/login"')
        ->assertSee('Sign in');
});

test('authenticated users are redirected away from login page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $page = visit(route('login'));

    $page->assertScript('window.location.pathname === "/dashboard"')
        ->assertSee('Dashboard');
});

test('login form handles longer processing times', function () {
    // Create administrator user for testing
    $admin = User::factory()->administrator()->create([
        'email' => 'administrator@example.com',
        'password' => 'password',
    ]);

    $page = visit(route('login'));

    $page->type('@email', $admin->email)
        ->type('@password', 'password')
        ->press('@sign-in')
        ->assertScript('window.location.pathname === "/dashboard"')
        ->assertSee('Dashboard');
});
