<?php

declare(strict_types=1);

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('browser tab and home screen icons', function (): void {
    it('links the Ringside icons from every page layout', function (Closure $visit): void {
        // Act
        $response = $visit();

        // Assert
        $response
            ->assertOk()
            ->assertSeeHtml('<link rel="icon" href="'.asset('favicon.ico').'" sizes="48x48" />')
            ->assertSeeHtml('<link rel="icon" href="'.asset('favicon.svg').'" type="image/svg+xml" />')
            ->assertSeeHtml('<link rel="apple-touch-icon" href="'.asset('apple-touch-icon.png').'" />');
    })->with([
        'marketing layout' => [fn () => get(route('home'))],
        'authentication layout' => [fn () => get(route('login'))],
        'application layout' => [fn () => actingAs(administrator())->get(route('dashboard'))],
    ]);

    it('ships an icon file for each link', function (string $file): void {
        // Act
        $size = filesize(public_path($file));

        // Assert
        expect($size)->toBeGreaterThan(0);
    })->with(['favicon.ico', 'favicon.svg', 'apple-touch-icon.png']);
});
