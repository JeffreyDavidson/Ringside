<?php

declare(strict_types=1);

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('browser tab and home screen icons', function (): void {
    it('links the Ringside icons from guest layouts', function (string $routeName): void {
        // Act
        $response = get(route($routeName));

        // Assert
        $response
            ->assertOk()
            ->assertSeeHtml('<link rel="icon" href="'.asset('favicon.ico').'" sizes="48x48" />')
            ->assertSeeHtml('<link rel="icon" href="'.asset('favicon.svg').'" type="image/svg+xml" />')
            ->assertSeeHtml('<link rel="apple-touch-icon" href="'.asset('apple-touch-icon.png').'" />');
    })->with([
        'marketing layout' => 'home',
        'authentication layout' => 'login',
    ]);

    it('links the Ringside icons from the application layout', function (): void {
        // Arrange
        actingAs(administrator());

        // Act
        $response = get(route('dashboard'));

        // Assert
        $response
            ->assertOk()
            ->assertSeeHtml('<link rel="icon" href="'.asset('favicon.ico').'" sizes="48x48" />')
            ->assertSeeHtml('<link rel="icon" href="'.asset('favicon.svg').'" type="image/svg+xml" />')
            ->assertSeeHtml('<link rel="apple-touch-icon" href="'.asset('apple-touch-icon.png').'" />');
    });

    it('ships an icon file for each link', function (string $file): void {
        // Act
        $size = filesize(public_path($file));

        // Assert
        expect($size)->toBeGreaterThan(0);
    })->with(['favicon.ico', 'favicon.svg', 'apple-touch-icon.png']);
});
