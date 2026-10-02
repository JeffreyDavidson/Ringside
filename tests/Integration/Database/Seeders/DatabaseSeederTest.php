<?php

declare(strict_types=1);

use App\Models\Users\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\UsersTableSeeder;

use function Pest\Laravel\seed;

test('the database seeder populates a fresh database', function (): void {
    // Act
    seed(DatabaseSeeder::class);

    // Assert
    expect(User::query()->count())->toBe(12);
});

test('demo seeders refuse to create known-password accounts in production', function (string $seederClass): void {
    // Arrange
    app()->detectEnvironment(fn (): string => 'production');

    // Act
    $seed = fn () => app($seederClass)->run();

    // Assert
    expect($seed)->toThrow(RuntimeException::class, 'must not run in production');
    expect(User::query()->count())->toBe(0);
})->with([DatabaseSeeder::class, UsersTableSeeder::class]);
