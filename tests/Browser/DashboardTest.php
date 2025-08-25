<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;

uses(DatabaseMigrations::class);

test('authenticated user can access dashboard', function () {
    $user = User::factory()->create([
        'email' => 'dashboard@test.com',
        'password' => 'password123',
    ]);

    $this->actingAs($user);

    $page = visit('/dashboard');

    $page->assertSee('Dashboard')
        ->assertNoJavascriptErrors();
});

test('unauthenticated users are redirected from dashboard', function () {
    $page = visit('/dashboard');

    // Should be redirected to login or see authentication prompt
    $page->assertSee('Sign in')
        ->assertNoJavascriptErrors();
});

test('dashboard page loads without errors', function () {
    $user = User::factory()->create([
        'email' => 'load@test.com',
        'password' => 'password123',
    ]);

    $this->actingAs($user);

    $page = visit('/dashboard');

    // Verify page loads without errors
    $page->assertDontSee('404')
        ->assertDontSee('500')
        ->assertDontSee('Error')
        ->assertNoJavascriptErrors();
});

test('dashboard has basic navigation structure', function () {
    $user = User::factory()->create([
        'email' => 'nav@test.com',
        'password' => 'password123',
    ]);

    $this->actingAs($user);

    $page = visit('/dashboard');

    // Check for basic page structure elements
    $page->assertElementPresent('nav')
        ->assertElementPresent('main')
        ->assertNoJavascriptErrors();
});
