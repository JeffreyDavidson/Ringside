<?php

declare(strict_types=1);

use App\Actions\Managers\ReleaseAction;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it releases an employed manager', function () {
    $manager = Manager::factory()->employed()->create();

    expect($manager->isEmployed())->toBeTrue();
    expect($manager->isReleased())->toBeFalse();

    ReleaseAction::run($manager);

    $manager->refresh();
    expect($manager->isReleased())->toBeTrue();
    expect($manager->isEmployed())->toBeFalse();

    // Verify employment was ended
    $this->assertDatabaseHas('managers_employments', [
        'manager_id' => $manager->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it releases manager with specific release date', function () {
    $manager = Manager::factory()->employed()->create();
    $releaseDate = now()->subDays(4);

    ReleaseAction::run($manager, $releaseDate);

    $manager->refresh();
    expect($manager->isReleased())->toBeTrue();

    // Verify employment ended with specific date
    $this->assertDatabaseHas('managers_employments', [
        'manager_id' => $manager->id,
        'ended_at' => $releaseDate->toDateTimeString(),
    ]);
});

test('it releases suspended manager and ends suspension', function () {
    $manager = Manager::factory()->employed()->suspended()->create();

    expect($manager->isSuspended())->toBeTrue();
    expect($manager->isEmployed())->toBeTrue();

    ReleaseAction::run($manager);

    $manager->refresh();
    expect($manager->isReleased())->toBeTrue();
    expect($manager->isSuspended())->toBeFalse();
    expect($manager->isEmployed())->toBeFalse();

    // Verify suspension was ended
    $this->assertDatabaseHas('managers_suspensions', [
        'manager_id' => $manager->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Verify employment was ended
    $this->assertDatabaseHas('managers_employments', [
        'manager_id' => $manager->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it releases injured manager and ends injury', function () {
    $manager = Manager::factory()->employed()->injured()->create();

    expect($manager->isInjured())->toBeTrue();
    expect($manager->isEmployed())->toBeTrue();

    ReleaseAction::run($manager);

    $manager->refresh();
    expect($manager->isReleased())->toBeTrue();
    expect($manager->isInjured())->toBeFalse();
    expect($manager->isEmployed())->toBeFalse();

    // Verify injury was ended
    $this->assertDatabaseHas('managers_injuries', [
        'manager_id' => $manager->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Verify employment was ended
    $this->assertDatabaseHas('managers_employments', [
        'manager_id' => $manager->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it ends management relationships with cascade strategy', function () {
    $manager = Manager::factory()->employed()->create();
    $wrestler = Wrestler::factory()->employed()->create();
    $tagTeam = TagTeam::factory()->create();

    // Assign manager to wrestler and tag team
    $manager->wrestlers()->attach($wrestler->id, ['hired_at' => now()->subDays(30)]);
    $manager->tagTeams()->attach($tagTeam->id, ['hired_at' => now()->subDays(20)]);

    expect($manager->currentWrestlers)->toHaveCount(1);
    expect($manager->currentTagTeams)->toHaveCount(1);

    ReleaseAction::run($manager);

    $manager->refresh();

    // Management relationships should be ended by cascade strategy
    expect($manager->currentWrestlers)->toHaveCount(0);
    expect($manager->currentTagTeams)->toHaveCount(0);

    // Verify relationships were ended with release date
    $this->assertDatabaseHas('wrestlers_managers', [
        'manager_id' => $manager->id,
        'wrestler_id' => $wrestler->id,
        'fired_at' => now()->toDateTimeString(),
    ]);

    $this->assertDatabaseHas('tag_teams_managers', [
        'manager_id' => $manager->id,
        'tag_team_id' => $tagTeam->id,
        'fired_at' => now()->toDateTimeString(),
    ]);
});

test('it uses StatusTransitionPipeline with cascade strategy', function () {
    $manager = Manager::factory()->employed()->create();
    $wrestler = Wrestler::factory()->employed()->create();

    // Set up management relationship
    $manager->wrestlers()->attach($wrestler->id, ['hired_at' => now()->subDay()]);

    expect($manager->isReleased())->toBeFalse();
    expect($manager->currentWrestlers)->toHaveCount(1);

    ReleaseAction::run($manager);

    $manager->refresh();

    // Verify release status through pipeline
    expect($manager->isReleased())->toBeTrue();
    expect($manager->isEmployed())->toBeFalse();

    // Verify cascade strategy ended relationships
    expect($manager->currentWrestlers)->toHaveCount(0);
});

test('it prevents releasing already released manager', function () {
    $manager = Manager::factory()->released()->create();

    expect($manager->isReleased())->toBeTrue();

    expect(fn () => ReleaseAction::run($manager))
        ->toThrow(Exception::class);
});

test('it prevents releasing unemployed manager', function () {
    $manager = Manager::factory()->create();

    expect($manager->isEmployed())->toBeFalse();

    expect(fn () => ReleaseAction::run($manager))
        ->toThrow(Exception::class);
});

test('it handles database transactions correctly', function () {
    $manager = Manager::factory()->employed()->suspended()->create();
    $wrestler = Wrestler::factory()->employed()->create();
    $manager->wrestlers()->attach($wrestler->id, ['hired_at' => now()->subDay()]);

    ReleaseAction::run($manager);

    $manager->refresh();

    // Verify transaction was successful - all operations completed
    expect($manager->isReleased())->toBeTrue();
    expect($manager->isEmployed())->toBeFalse();
    expect($manager->isSuspended())->toBeFalse();
    expect($manager->currentWrestlers)->toHaveCount(0);

    // Verify all database changes are consistent
    $this->assertDatabaseHas('managers_employments', [
        'manager_id' => $manager->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    $this->assertDatabaseHas('managers_suspensions', [
        'manager_id' => $manager->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it uses DateHelper for consistent date handling', function () {
    $manager = Manager::factory()->employed()->suspended()->create();
    $customReleaseDate = now()->subDays(2)->startOfDay();

    ReleaseAction::run($manager, $customReleaseDate);

    $manager->refresh();

    // Verify DateHelper was used for date resolution across all operations
    $this->assertDatabaseHas('managers_employments', [
        'manager_id' => $manager->id,
        'ended_at' => $customReleaseDate->toDateTimeString(),
    ]);

    $this->assertDatabaseHas('managers_suspensions', [
        'manager_id' => $manager->id,
        'ended_at' => $customReleaseDate->toDateTimeString(),
    ]);
});

test('it preserves management history during release', function () {
    $manager = Manager::factory()->employed()->create();
    $wrestler = Wrestler::factory()->employed()->create();

    // Create historical and current management relationships
    $manager->wrestlers()->attach($wrestler->id, [
        'hired_at' => now()->subDays(30),
        'fired_at' => now()->subDays(20), // Historical relationship
    ]);
    $manager->wrestlers()->attach($wrestler->id, [
        'hired_at' => now()->subDays(10), // Current relationship
    ]);

    expect($manager->wrestlers()->count())->toBe(2); // Total relationships
    expect($manager->currentWrestlers)->toHaveCount(1); // Current relationships

    ReleaseAction::run($manager);

    $manager->refresh();

    // Should preserve all historical relationships while ending current ones
    expect($manager->wrestlers()->count())->toBe(2); // Historical preserved
    expect($manager->currentWrestlers)->toHaveCount(0); // Current ended

    // Verify the current relationship was ended with release date
    $currentRelationship = $manager->wrestlers()
        ->wherePivot('hired_at', now()->subDays(10)->toDateTimeString())
        ->first();

    expect($currentRelationship->pivot->fired_at)->toBe(now()->toDateTimeString());
});

test('it handles manager with no management relationships', function () {
    $manager = Manager::factory()->employed()->create();

    expect($manager->currentWrestlers)->toHaveCount(0);
    expect($manager->currentTagTeams)->toHaveCount(0);

    ReleaseAction::run($manager);

    $manager->refresh();

    // Should release successfully even without relationships
    expect($manager->isReleased())->toBeTrue();
    expect($manager->isEmployed())->toBeFalse();

    $this->assertDatabaseHas('managers_employments', [
        'manager_id' => $manager->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it handles complex status combinations', function () {
    $manager = Manager::factory()->employed()->create();

    // Manually set up a complex status (shouldn't be possible normally)
    $manager->suspensions()->create(['started_at' => now()->subDays(5), 'ended_at' => now()->subDays(3)]);
    $manager->suspensions()->create(['started_at' => now()->subDays(2), 'ended_at' => null]); // Current

    $manager->refresh();
    expect($manager->isSuspended())->toBeTrue();
    expect($manager->isEmployed())->toBeTrue();

    ReleaseAction::run($manager);

    $manager->refresh();

    // Should handle complex status properly
    expect($manager->isReleased())->toBeTrue();
    expect($manager->isSuspended())->toBeFalse();
    expect($manager->isEmployed())->toBeFalse();

    // Should end only the current suspension
    expect($manager->suspensions()->whereNull('ended_at')->count())->toBe(0);
    expect($manager->suspensions()->count())->toBe(2); // Preserve historical
});
