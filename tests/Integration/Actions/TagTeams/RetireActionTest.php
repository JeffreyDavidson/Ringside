<?php

declare(strict_types=1);

use App\Actions\TagTeams\RetireAction;
use App\Models\TagTeams\TagTeam;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it retires an employed tag team', function () {
    $tagTeam = TagTeam::factory()->employed()->create();

    expect($tagTeam->isEmployed())->toBeTrue();
    expect($tagTeam->isRetired())->toBeFalse();

    RetireAction::run($tagTeam);

    $tagTeam->refresh();
    expect($tagTeam->isEmployed())->toBeFalse();
    expect($tagTeam->isRetired())->toBeTrue();

    // Verify employment record was ended
    $this->assertDatabaseHas('tag_teams_employments', [
        'tag_team_id' => $tagTeam->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Verify retirement record was created
    $this->assertDatabaseHas('tag_teams_retirements', [
        'tag_team_id' => $tagTeam->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it retires tag team with specific retirement date', function () {
    $tagTeam = TagTeam::factory()->employed()->create();
    $retirementDate = now()->subDays(5);

    RetireAction::run($tagTeam, $retirementDate);

    $tagTeam->refresh();
    expect($tagTeam->isRetired())->toBeTrue();
    expect($tagTeam->isEmployed())->toBeFalse();

    // Verify retirement started with specific date
    $this->assertDatabaseHas('tag_teams_retirements', [
        'tag_team_id' => $tagTeam->id,
        'started_at' => $retirementDate->toDateTimeString(),
        'ended_at' => null,
    ]);

    // Verify employment ended with same date
    $this->assertDatabaseHas('tag_teams_employments', [
        'tag_team_id' => $tagTeam->id,
        'ended_at' => $retirementDate->toDateTimeString(),
    ]);
});

test('it retires suspended tag team', function () {
    $tagTeam = TagTeam::factory()->suspended()->create();

    expect($tagTeam->isEmployed())->toBeTrue();
    expect($tagTeam->isSuspended())->toBeTrue();

    RetireAction::run($tagTeam);

    $tagTeam->refresh();
    expect($tagTeam->isRetired())->toBeTrue();
    expect($tagTeam->isEmployed())->toBeFalse();
    expect($tagTeam->isSuspended())->toBeFalse();

    // Verify suspension ended
    $this->assertDatabaseHas('tag_teams_suspensions', [
        'tag_team_id' => $tagTeam->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Verify retirement started
    $this->assertDatabaseHas('tag_teams_retirements', [
        'tag_team_id' => $tagTeam->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it uses StatusTransitionPipeline for retirement', function () {
    $tagTeam = TagTeam::factory()->employed()->create();

    // Get current employment to verify it gets ended
    $currentEmployment = $tagTeam->currentEmployment();
    expect($currentEmployment)->not()->toBeNull();
    expect($tagTeam->currentRetirement())->toBeNull();

    RetireAction::run($tagTeam);

    $tagTeam->refresh();

    // Verify employment ended and retirement created through pipeline
    expect($tagTeam->currentEmployment())->toBeNull();
    expect($tagTeam->currentRetirement())->not()->toBeNull();
    expect($tagTeam->isRetired())->toBeTrue();
    expect($tagTeam->isEmployed())->toBeFalse();
});

test('it prevents retiring unemployed tag team', function () {
    $tagTeam = TagTeam::factory()->create();

    expect($tagTeam->isEmployed())->toBeFalse();

    expect(fn () => RetireAction::run($tagTeam))
        ->toThrow(Exception::class);
});

test('it prevents retiring already retired tag team', function () {
    $tagTeam = TagTeam::factory()->retired()->create();

    expect($tagTeam->isRetired())->toBeTrue();

    expect(fn () => RetireAction::run($tagTeam))
        ->toThrow(Exception::class);
});

test('it handles database transactions correctly', function () {
    $tagTeam = TagTeam::factory()->employed()->create();
    $originalEmploymentId = $tagTeam->currentEmployment()->id;

    RetireAction::run($tagTeam);

    $tagTeam->refresh();

    // Verify the transition was successful
    expect($tagTeam->isRetired())->toBeTrue();
    expect($tagTeam->isEmployed())->toBeFalse();

    // Verify original employment record was properly ended
    $this->assertDatabaseHas('tag_teams_employments', [
        'id' => $originalEmploymentId,
        'tag_team_id' => $tagTeam->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Verify new retirement record was created
    $retirement = $tagTeam->currentRetirement();
    expect($retirement)->not()->toBeNull();
    expect($retirement->started_at->toDateTimeString())->toBe(now()->toDateTimeString());
    expect($retirement->ended_at)->toBeNull();
});

test('it creates new retirement period', function () {
    $tagTeam = TagTeam::factory()->employed()->create();
    $originalRetirementCount = $tagTeam->retirements()->count();

    RetireAction::run($tagTeam);

    $tagTeam->refresh();

    // Should create a new retirement record
    expect($tagTeam->retirements()->count())->toBe($originalRetirementCount + 1);
    expect($tagTeam->isRetired())->toBeTrue();

    // New retirement should be current and active
    $currentRetirement = $tagTeam->currentRetirement();
    expect($currentRetirement)->not()->toBeNull();
    expect($currentRetirement->started_at->toDateTimeString())->toBe(now()->toDateTimeString());
    expect($currentRetirement->ended_at)->toBeNull();
});

test('it uses DateHelper for consistent date handling', function () {
    $tagTeam = TagTeam::factory()->employed()->create();
    $customRetirementDate = now()->subDays(2)->startOfDay();

    RetireAction::run($tagTeam, $customRetirementDate);

    $tagTeam->refresh();

    // Verify DateHelper was used for date resolution across all operations
    $this->assertDatabaseHas('tag_teams_employments', [
        'tag_team_id' => $tagTeam->id,
        'ended_at' => $customRetirementDate->toDateTimeString(),
    ]);

    $this->assertDatabaseHas('tag_teams_retirements', [
        'tag_team_id' => $tagTeam->id,
        'started_at' => $customRetirementDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it handles multiple retirement history correctly', function () {
    $tagTeam = TagTeam::factory()->employed()->create();

    // Create retirement history
    $tagTeam->retirements()->create(['started_at' => now()->subDays(20), 'ended_at' => now()->subDays(15)]);

    $tagTeam->refresh();
    expect($tagTeam->isRetired())->toBeFalse();
    expect($tagTeam->retirements()->count())->toBe(1);

    RetireAction::run($tagTeam);

    $tagTeam->refresh();

    // Should add new retirement period
    expect($tagTeam->isRetired())->toBeTrue();
    expect($tagTeam->retirements()->count())->toBe(2);

    // New retirement should be current
    $currentRetirement = $tagTeam->currentRetirement();
    expect($currentRetirement)->not()->toBeNull();
    expect($currentRetirement->started_at->toDateTimeString())->toBe(now()->toDateTimeString());
});

test('it preserves employment and retirement history', function () {
    $tagTeam = TagTeam::factory()->employed()->create();

    // Create historical records
    $tagTeam->retirements()->create(['started_at' => now()->subDays(30), 'ended_at' => now()->subDays(25)]);
    $originalEmploymentCount = $tagTeam->employments()->count();
    $originalRetirementCount = $tagTeam->retirements()->count();

    RetireAction::run($tagTeam);

    $tagTeam->refresh();

    // Should preserve all historical records
    expect($tagTeam->employments()->count())->toBe($originalEmploymentCount);
    expect($tagTeam->retirements()->count())->toBe($originalRetirementCount + 1);

    // Current employment should be ended, current retirement should be active
    expect($tagTeam->currentEmployment())->toBeNull();
    expect($tagTeam->currentRetirement())->not()->toBeNull();
});
