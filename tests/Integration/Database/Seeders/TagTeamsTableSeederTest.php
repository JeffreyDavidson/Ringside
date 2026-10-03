<?php

declare(strict_types=1);

use App\Enums\Shared\EmploymentStatus;
use App\Models\Roster\TagTeams\TagTeam;
use Database\Seeders\TagTeamsTableSeeder;
use Illuminate\Support\Facades\Artisan;

/**
 * Integration tests for TagTeamsTableSeeder data seeding and validation.
 *
 * Seeding takes over a second, so every assertion about one seeded data set shares a single run instead of each
 * test seeding again.
 *
 * @see TagTeamsTableSeeder
 */
describe('TagTeamsTableSeeder Integration Tests', function () {
    test('seeds tag teams with complete, realistic and unique records', function () {
        // Act
        $exitCode = Artisan::call('db:seed', ['--class' => 'TagTeamsTableSeeder']);

        // Assert
        $tagTeams = TagTeam::all();

        expect($exitCode)->toBe(0)
            ->and($tagTeams)->not->toBeEmpty()
            ->and($tagTeams->pluck('name')->unique())->toHaveCount($tagTeams->count());

        foreach ($tagTeams as $tagTeam) {
            expect($tagTeam->name)->toBeString()->not->toBeEmpty()
                ->and(str_word_count($tagTeam->name))->toBeGreaterThanOrEqual(2)
                ->and($tagTeam->name)->not->toContain('Test')
                ->and($tagTeam->status)->toBeInstanceOf(EmploymentStatus::class);
        }
    });

    test('seeding again keeps the existing tag teams', function () {
        // Arrange
        Artisan::call('db:seed', ['--class' => 'TagTeamsTableSeeder']);
        $initialCount = TagTeam::count();

        // Act
        Artisan::call('db:seed', ['--class' => 'TagTeamsTableSeeder']);

        // Assert
        expect(TagTeam::count())->toBeGreaterThanOrEqual($initialCount);
    });
});
