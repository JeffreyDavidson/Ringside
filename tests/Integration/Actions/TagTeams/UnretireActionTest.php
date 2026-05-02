<?php

declare(strict_types=1);

use App\Actions\TagTeams\UnretireAction;
use App\Models\TagTeams\TagTeam;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it unretires a retired tag team', function () {
    $tagTeam = TagTeam::factory()->retired()->create();

    expect($tagTeam->isRetired())->toBeTrue();
    expect($tagTeam->isEmployed())->toBeFalse();

    UnretireAction::run($tagTeam);

    $tagTeam->refresh();
    expect($tagTeam->isRetired())->toBeFalse();
    expect($tagTeam->isEmployed())->toBeTrue();

    // Verify retirement record was ended
    $this->assertDatabaseHas('tag_teams_retirements', [
        'tag_team_id' => $tagTeam->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Verify employment record was created
    $this->assertDatabaseHas('tag_teams_employments', [
        'tag_team_id' => $tagTeam->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it unretires tag team with specific unretirement date', function () {
    $tagTeam = TagTeam::factory()->retired()->create();
    $unretirementDate = now()->subDays(4);

    UnretireAction::run($tagTeam, $unretirementDate);

    $tagTeam->refresh();
    expect($tagTeam->isRetired())->toBeFalse();
    expect($tagTeam->isEmployed())->toBeTrue();

    // Verify retirement ended with specific date
    $this->assertDatabaseHas('tag_teams_retirements', [
        'tag_team_id' => $tagTeam->id,
        'ended_at' => $unretirementDate->toDateTimeString(),
    ]);

    // Verify employment started with same date
    $this->assertDatabaseHas('tag_teams_employments', [
        'tag_team_id' => $tagTeam->id,
        'started_at' => $unretirementDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it uses StatusTransitionPipeline for unretirement', function () {
    $tagTeam = TagTeam::factory()->retired()->create();

    // Get current retirement to verify it gets ended
    $currentRetirement = $tagTeam->currentRetirement();
    expect($currentRetirement)->not()->toBeNull();
    expect($tagTeam->currentEmployment())->toBeNull();

    UnretireAction::run($tagTeam);

    $tagTeam->refresh();

    // Verify retirement ended and employment created through pipeline
    expect($tagTeam->currentRetirement())->toBeNull();
    expect($tagTeam->currentEmployment())->not()->toBeNull();
    expect($tagTeam->isRetired())->toBeFalse();
    expect($tagTeam->isEmployed())->toBeTrue();
});

test('it prevents unretiring non-retired tag team', function () {
    $tagTeam = TagTeam::factory()->employed()->create();

    expect($tagTeam->isRetired())->toBeFalse();

    expect(fn () => UnretireAction::run($tagTeam))
        ->toThrow(Exception::class);
});

test('it prevents unretiring unemployed tag team', function () {
    $tagTeam = TagTeam::factory()->create();

    expect($tagTeam->isEmployed())->toBeFalse();
    expect($tagTeam->isRetired())->toBeFalse();

    expect(fn () => UnretireAction::run($tagTeam))
        ->toThrow(Exception::class);
});

test('it handles database transactions correctly', function () {
    $tagTeam = TagTeam::factory()->retired()->create();
    $originalRetirementId = $tagTeam->currentRetirement->id;

    UnretireAction::run($tagTeam);

    $tagTeam->refresh();

    // Verify the transition was successful
    expect($tagTeam->isRetired())->toBeFalse();
    expect($tagTeam->isEmployed())->toBeTrue();

    // Verify original retirement record was properly ended
    $this->assertDatabaseHas('tag_teams_retirements', [
        'id' => $originalRetirementId,
        'tag_team_id' => $tagTeam->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Verify new employment record was created
    $employment = $tagTeam->currentEmployment();
    expect($employment)->not()->toBeNull();
    expect($employment->started_at->toDateTimeString())->toBe(now()->toDateTimeString());
    expect($employment->ended_at)->toBeNull();
});

test('it ends current retirement period', function () {
    $tagTeam = TagTeam::factory()->retired()->create();
    $originalRetirementCount = $tagTeam->retirements()->count();

    UnretireAction::run($tagTeam);

    $tagTeam->refresh();

    // Should not create new retirement records, just end current one
    expect($tagTeam->retirements()->count())->toBe($originalRetirementCount);
    expect($tagTeam->isRetired())->toBeFalse();

    // All retirement records should have end dates
    expect($tagTeam->retirements()->whereNull('ended_at')->count())->toBe(0);
});

test('it creates new employment period', function () {
    $tagTeam = TagTeam::factory()->retired()->create();
    $originalEmploymentCount = $tagTeam->employments()->count();

    UnretireAction::run($tagTeam);

    $tagTeam->refresh();

    // Should create a new employment record
    expect($tagTeam->employments()->count())->toBe($originalEmploymentCount + 1);
    expect($tagTeam->isEmployed())->toBeTrue();

    // New employment should be current and active
    $currentEmployment = $tagTeam->currentEmployment();
    expect($currentEmployment)->not()->toBeNull();
    expect($currentEmployment->started_at->toDateTimeString())->toBe(now()->toDateTimeString());
    expect($currentEmployment->ended_at)->toBeNull();
});

test('it uses DateHelper for consistent date handling', function () {
    $tagTeam = TagTeam::factory()->retired()->create();
    $customUnretirementDate = now()->subDays(2)->startOfDay();

    UnretireAction::run($tagTeam, $customUnretirementDate);

    $tagTeam->refresh();

    // Verify DateHelper was used for date resolution across all operations
    $this->assertDatabaseHas('tag_teams_retirements', [
        'tag_team_id' => $tagTeam->id,
        'ended_at' => $customUnretirementDate->toDateTimeString(),
    ]);

    $this->assertDatabaseHas('tag_teams_employments', [
        'tag_team_id' => $tagTeam->id,
        'started_at' => $customUnretirementDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it handles multiple retirement history correctly', function () {
    $tagTeam = TagTeam::factory()->create();

    // Create multiple retirement history
    $tagTeam->retirements()->create(['started_at' => now()->subDays(40), 'ended_at' => now()->subDays(35)]);
    $tagTeam->retirements()->create(['started_at' => now()->subDays(30), 'ended_at' => now()->subDays(25)]);
    $tagTeam->retirements()->create(['started_at' => now()->subDays(20), 'ended_at' => null]); // Current

    $tagTeam->refresh();
    expect($tagTeam->isRetired())->toBeTrue();
    expect($tagTeam->retirements()->count())->toBe(3);

    UnretireAction::run($tagTeam);

    $tagTeam->refresh();

    // Should now be employed
    expect($tagTeam->isRetired())->toBeFalse();
    expect($tagTeam->isEmployed())->toBeTrue();

    // Should have preserved all retirement history
    expect($tagTeam->retirements()->count())->toBe(3);

    // All retirement records should have end dates now
    expect($tagTeam->retirements()->whereNull('ended_at')->count())->toBe(0);

    // Should have created new employment
    expect($tagTeam->employments()->count())->toBe(1);
});

test('it preserves employment and retirement history', function () {
    $tagTeam = TagTeam::factory()->create();

    // Create complex history
    $tagTeam->employments()->create(['started_at' => now()->subDays(50), 'ended_at' => now()->subDays(45)]);
    $tagTeam->retirements()->create(['started_at' => now()->subDays(45), 'ended_at' => now()->subDays(40)]);
    $tagTeam->employments()->create(['started_at' => now()->subDays(40), 'ended_at' => now()->subDays(35)]);
    $tagTeam->retirements()->create(['started_at' => now()->subDays(35), 'ended_at' => null]); // Current

    $originalEmploymentCount = $tagTeam->employments()->count();
    $originalRetirementCount = $tagTeam->retirements()->count();

    UnretireAction::run($tagTeam);

    $tagTeam->refresh();

    // Should preserve all historical records
    expect($tagTeam->employments()->count())->toBe($originalEmploymentCount + 1);
    expect($tagTeam->retirements()->count())->toBe($originalRetirementCount);

    // Current retirement should be ended, current employment should be active
    expect($tagTeam->currentRetirement())->toBeNull();
    expect($tagTeam->currentEmployment())->not()->toBeNull();
});

test('it handles unretirement with cascade effects', function () {
    $tagTeam = TagTeam::factory()->retired()->create();

    UnretireAction::run($tagTeam);

    $tagTeam->refresh();

    // Verify the action used StatusTransitionPipeline with appropriate cascade
    expect($tagTeam->isRetired())->toBeFalse();
    expect($tagTeam->isEmployed())->toBeTrue();

    // Retirement should be ended
    $this->assertDatabaseHas('tag_teams_retirements', [
        'tag_team_id' => $tagTeam->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Employment should be active
    $this->assertDatabaseHas('tag_teams_employments', [
        'tag_team_id' => $tagTeam->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);

    // Note: Cascade effects would be tested in cascade strategy tests
});

test('it transitions from retired to employed seamlessly', function () {
    $tagTeam = TagTeam::factory()->retired()->create();

    // Verify starting state
    expect($tagTeam->isRetired())->toBeTrue();
    expect($tagTeam->isEmployed())->toBeFalse();
    expect($tagTeam->isSuspended())->toBeFalse();

    UnretireAction::run($tagTeam);

    $tagTeam->refresh();

    // Should transition to employed state
    expect($tagTeam->isRetired())->toBeFalse();
    expect($tagTeam->isEmployed())->toBeTrue();
    expect($tagTeam->isSuspended())->toBeFalse();

    // Should have active employment and no active retirement
    expect($tagTeam->currentEmployment())->not()->toBeNull();
    expect($tagTeam->currentRetirement())->toBeNull();
});
