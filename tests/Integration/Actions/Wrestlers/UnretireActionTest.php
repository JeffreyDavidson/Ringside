<?php

declare(strict_types=1);

use App\Actions\Wrestlers\UnretireAction;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Wrestlers\Wrestler;

// The rules shared with managers and referees are in tests/Integration/Actions/Individuals/UnretireActionTest.php.

test('it employs the wrestler and also employs unemployed managers', function () {
    // Arrange
    $wrestler = Wrestler::factory()->retired()->create();
    $unemployedManager = Manager::factory()->create();
    $employedManager = Manager::factory()->employed()->create();
    $employedManagerEmployment = $employedManager->currentEmployment()->firstOrFail();
    $wrestler->managers()->attach($unemployedManager->id, ['hired_at' => now()->subDays(10)]);
    $wrestler->managers()->attach($employedManager->id, ['hired_at' => now()->subDays(5)]);

    // Act
    resolve(UnretireAction::class)->handle($wrestler);

    // Assert
    expect($wrestler->refresh()->currentEmployment()->exists())->toBeTrue()
        ->and($unemployedManager->refresh()->currentEmployment()->exists())->toBeTrue()
        ->and($employedManager->refresh()->employments()->count())->toBe(1)
        ->and($employedManager->currentEmployment()->firstOrFail()->id)->toBe($employedManagerEmployment->id);
});

test('it does not employ the wrestler or its managers when not employing immediately', function () {
    // Arrange
    $wrestler = Wrestler::factory()->retired()->create();
    $manager = Manager::factory()->create();
    $wrestler->managers()->attach($manager->id, ['hired_at' => now()->subDays(5)]);

    // Act
    resolve(UnretireAction::class)->handle($wrestler, null, false);

    // Assert
    expect($wrestler->refresh()->currentRetirement()->exists())->toBeFalse()
        ->and($wrestler->currentEmployment()->exists())->toBeFalse()
        ->and($manager->refresh()->employments()->count())->toBe(0);
});
