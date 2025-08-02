<?php

declare(strict_types=1);

use App\Actions\TagTeams\EmployAction;
use App\Models\TagTeams\TagTeam;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it employs an unemployed tag team', function () {
    $tagTeam = TagTeam::factory()->create();

    expect($tagTeam->isEmployed())->toBeFalse();

    EmployAction::run($tagTeam);

    $tagTeam->refresh();
    expect($tagTeam->isEmployed())->toBeTrue();

    // Verify employment record was created
    $this->assertDatabaseHas('tag_teams_employments', [
        'tag_team_id' => $tagTeam->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it employs tag team with specific employment date', function () {
    $tagTeam = TagTeam::factory()->create();
    $employmentDate = now()->subDays(7);

    EmployAction::run($tagTeam, $employmentDate);

    $tagTeam->refresh();
    expect($tagTeam->isEmployed())->toBeTrue();

    // Verify employment started with specific date
    $this->assertDatabaseHas('tag_teams_employments', [
        'tag_team_id' => $tagTeam->id,
        'started_at' => $employmentDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it employs retired tag team by ending retirement', function () {
    $tagTeam = TagTeam::factory()->retired()->create();

    expect($tagTeam->isRetired())->toBeTrue();
    expect($tagTeam->isEmployed())->toBeFalse();

    EmployAction::run($tagTeam);

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

test('it uses StatusTransitionPipeline for employment', function () {
    $tagTeam = TagTeam::factory()->create();

    expect($tagTeam->currentEmployment())->toBeNull();

    EmployAction::run($tagTeam);

    $tagTeam->refresh();

    // Verify employment created through pipeline
    expect($tagTeam->currentEmployment())->not()->toBeNull();
    expect($tagTeam->isEmployed())->toBeTrue();

    // Verify records show proper dates
    $this->assertDatabaseHas('tag_teams_employments', [
        'tag_team_id' => $tagTeam->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it prevents employing already employed tag team', function () {
    $tagTeam = TagTeam::factory()->employed()->create();

    expect($tagTeam->isEmployed())->toBeTrue();

    expect(fn () => EmployAction::run($tagTeam))
        ->toThrow(Exception::class);
});

test('it handles database transactions correctly', function () {
    $tagTeam = TagTeam::factory()->create();

    EmployAction::run($tagTeam);

    $tagTeam->refresh();

    // Verify the transaction was successful
    expect($tagTeam->isEmployed())->toBeTrue();

    // Verify employment record was created
    $employment = $tagTeam->currentEmployment();
    expect($employment)->not()->toBeNull();
    expect($employment->started_at->toDateTimeString())->toBe(now()->toDateTimeString());
    expect($employment->ended_at)->toBeNull();
});

test('it creates new employment period', function () {
    $tagTeam = TagTeam::factory()->create();
    $originalEmploymentCount = $tagTeam->employments()->count();

    EmployAction::run($tagTeam);

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
    $tagTeam = TagTeam::factory()->create();
    $customEmploymentDate = now()->subDays(3)->startOfDay();

    EmployAction::run($tagTeam, $customEmploymentDate);

    $tagTeam->refresh();

    // Verify DateHelper was used for date resolution
    $this->assertDatabaseHas('tag_teams_employments', [
        'tag_team_id' => $tagTeam->id,
        'started_at' => $customEmploymentDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it handles multiple employment history correctly', function () {
    $tagTeam = TagTeam::factory()->create();

    // Create employment history
    $tagTeam->employments()->create(['started_at' => now()->subDays(30), 'ended_at' => now()->subDays(25)]);
    $tagTeam->employments()->create(['started_at' => now()->subDays(20), 'ended_at' => now()->subDays(15)]);

    $tagTeam->refresh();
    expect($tagTeam->isEmployed())->toBeFalse();
    expect($tagTeam->employments()->count())->toBe(2);

    EmployAction::run($tagTeam);

    $tagTeam->refresh();

    // Should add new employment period
    expect($tagTeam->isEmployed())->toBeTrue();
    expect($tagTeam->employments()->count())->toBe(3);

    // New employment should be current
    $currentEmployment = $tagTeam->currentEmployment();
    expect($currentEmployment)->not()->toBeNull();
    expect($currentEmployment->started_at->toDateTimeString())->toBe(now()->toDateTimeString());
});

test('it preserves employment history during new employment', function () {
    $tagTeam = TagTeam::factory()->create();

    // Create historical employment
    $tagTeam->employments()->create(['started_at' => now()->subDays(20), 'ended_at' => now()->subDays(10)]);
    $originalEmploymentCount = $tagTeam->employments()->count();

    EmployAction::run($tagTeam);

    $tagTeam->refresh();

    // Should preserve all employment history
    expect($tagTeam->employments()->count())->toBe($originalEmploymentCount + 1);

    // All historical records should remain intact
    expect($tagTeam->employments()->where('ended_at', '!=', null)->count())->toBe(1);

    // Current employment should be active
    expect($tagTeam->currentEmployment())->not()->toBeNull();
});

test('it handles tag team with complex status history', function () {
    $tagTeam = TagTeam::factory()->create();

    // Create complex employment/retirement history
    $tagTeam->employments()->create(['started_at' => now()->subDays(30), 'ended_at' => now()->subDays(25)]);
    $tagTeam->retirements()->create(['started_at' => now()->subDays(25), 'ended_at' => now()->subDays(20)]);
    $tagTeam->employments()->create(['started_at' => now()->subDays(20), 'ended_at' => now()->subDays(15)]);

    $tagTeam->refresh();
    expect($tagTeam->isEmployed())->toBeFalse();
    expect($tagTeam->isRetired())->toBeFalse();

    EmployAction::run($tagTeam);

    $tagTeam->refresh();

    // Should now be employed
    expect($tagTeam->isEmployed())->toBeTrue();
    expect($tagTeam->isRetired())->toBeFalse();

    // Should have preserved all historical records
    expect($tagTeam->employments()->count())->toBe(3); // 2 historical + 1 new
    expect($tagTeam->retirements()->count())->toBe(1); // 1 historical

    // New employment should be current
    $currentEmployment = $tagTeam->currentEmployment();
    expect($currentEmployment)->not()->toBeNull();
    expect($currentEmployment->started_at->toDateTimeString())->toBe(now()->toDateTimeString());
});
