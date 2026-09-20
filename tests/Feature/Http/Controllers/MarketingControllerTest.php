<?php

declare(strict_types=1);

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('guests can explore Ringside and reach sign in', function () {
    // Act
    $response = get(route('home'));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('marketing')
        ->assertSee(__('marketing.hero.first'))
        ->assertSee(__('marketing.hero.second'))
        ->assertSee(__('marketing.roster.title'))
        ->assertSee(__('marketing.show.title'))
        ->assertSee(__('marketing.championships.title'))
        ->assertSeeHtml('href="'.route('login').'"')
        ->assertDontSeeHtml('href="'.route('dashboard').'"');
});

test('signed in users can explore Ringside and return to their dashboard', function () {
    // Arrange
    $user = basicUser();
    actingAs($user);

    // Act
    $response = get(route('home'));

    // Assert
    $response
        ->assertOk()
        ->assertSee(__('marketing.dashboard'))
        ->assertSeeHtml('href="'.route('dashboard').'"')
        ->assertDontSeeHtml('href="'.route('login').'"');
});
