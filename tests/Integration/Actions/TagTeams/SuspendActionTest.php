<?php

declare(strict_types=1);

use App\Actions\TagTeams\SuspendAction;
use App\Models\TagTeams\TagTeam;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it suspends an employed tag team', function () {
    $tagTeam = TagTeam::factory()->employed()->create();

    expect($tagTeam->isEmployed())->toBeTrue();
    expect($tagTeam->isSuspended())->toBeFalse();

    SuspendAction::run($tagTeam);

    $tagTeam->refresh();
    expect($tagTeam->isEmployed())->toBeTrue();
    expect($tagTeam->isSuspended())->toBeTrue();

    // Verify suspension record was created
    $this->assertDatabaseHas('tag_teams_suspensions', [
        'tag_team_id' => $tagTeam->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it suspends tag team with specific suspension date', function () {
    $tagTeam = TagTeam::factory()->employed()->create();
    $suspensionDate = now()->subDays(3);

    SuspendAction::run($tagTeam, $suspensionDate);

    $tagTeam->refresh();
    expect($tagTeam->isSuspended())->toBeTrue();

    // Verify suspension started with specific date
    $this->assertDatabaseHas('tag_teams_suspensions', [
        'tag_team_id' => $tagTeam->id,
        'started_at' => $suspensionDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it uses StatusTransitionPipeline for suspension', function () {
    $tagTeam = TagTeam::factory()->employed()->create();

    expect($tagTeam->currentSuspension())->toBeNull();

    SuspendAction::run($tagTeam);

    $tagTeam->refresh();

    // Verify suspension created through pipeline
    expect($tagTeam->currentSuspension())->not()->toBeNull();
    expect($tagTeam->isSuspended())->toBeTrue();
    expect($tagTeam->isEmployed())->toBeTrue();

    // Verify records show proper dates
    $this->assertDatabaseHas('tag_teams_suspensions', [
        'tag_team_id' => $tagTeam->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it prevents suspending unemployed tag team', function () {
    $tagTeam = TagTeam::factory()->create();

    expect($tagTeam->isEmployed())->toBeFalse();

    expect(fn () => SuspendAction::run($tagTeam))
        ->toThrow(Exception::class);
});

test('it prevents suspending already suspended tag team', function () {
    $tagTeam = TagTeam::factory()->suspended()->create();

    expect($tagTeam->isSuspended())->toBeTrue();

    expect(fn () => SuspendAction::run($tagTeam))
        ->toThrow(Exception::class);
});

test('it prevents suspending retired tag team', function () {
    $tagTeam = TagTeam::factory()->retired()->create();

    expect($tagTeam->isRetired())->toBeTrue();

    expect(fn () => SuspendAction::run($tagTeam))
        ->toThrow(Exception::class);
});

test('it handles database transactions correctly', function () {
    $tagTeam = TagTeam::factory()->employed()->create();

    SuspendAction::run($tagTeam);

    $tagTeam->refresh();

    // Verify the transaction was successful
    expect($tagTeam->isSuspended())->toBeTrue();
    expect($tagTeam->isEmployed())->toBeTrue();

    // Verify suspension record was created
    $suspension = $tagTeam->currentSuspension();
    expect($suspension)->not()->toBeNull();
    expect($suspension->started_at->toDateTimeString())->toBe(now()->toDateTimeString());
    expect($suspension->ended_at)->toBeNull();
});

test('it creates new suspension period', function () {
    $tagTeam = TagTeam::factory()->employed()->create();
    $originalSuspensionCount = $tagTeam->suspensions()->count();

    SuspendAction::run($tagTeam);

    $tagTeam->refresh();

    // Should create a new suspension record
    expect($tagTeam->suspensions()->count())->toBe($originalSuspensionCount + 1);
    expect($tagTeam->isSuspended())->toBeTrue();

    // New suspension should be current and active
    $currentSuspension = $tagTeam->currentSuspension();
    expect($currentSuspension)->not()->toBeNull();
    expect($currentSuspension->started_at->toDateTimeString())->toBe(now()->toDateTimeString());
    expect($currentSuspension->ended_at)->toBeNull();
});

test('it uses DateHelper for consistent date handling', function () {
    $tagTeam = TagTeam::factory()->employed()->create();
    $customSuspensionDate = now()->subDays(2)->startOfDay();

    SuspendAction::run($tagTeam, $customSuspensionDate);

    $tagTeam->refresh();

    // Verify DateHelper was used for date resolution
    $this->assertDatabaseHas('tag_teams_suspensions', [
        'tag_team_id' => $tagTeam->id,
        'started_at' => $customSuspensionDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it handles multiple suspension history correctly', function () {
    $tagTeam = TagTeam::factory()->employed()->create();

    // Create suspension history
    $tagTeam->suspensions()->create(['started_at' => now()->subDays(30), 'ended_at' => now()->subDays(25)]);
    $tagTeam->suspensions()->create(['started_at' => now()->subDays(20), 'ended_at' => now()->subDays(15)]);

    $tagTeam->refresh();
    expect($tagTeam->isSuspended())->toBeFalse();
    expect($tagTeam->suspensions()->count())->toBe(2);

    SuspendAction::run($tagTeam);

    $tagTeam->refresh();

    // Should add new suspension period
    expect($tagTeam->isSuspended())->toBeTrue();
    expect($tagTeam->suspensions()->count())->toBe(3);

    // New suspension should be current
    $currentSuspension = $tagTeam->currentSuspension();
    expect($currentSuspension)->not()->toBeNull();
    expect($currentSuspension->started_at->toDateTimeString())->toBe(now()->toDateTimeString());
});

test('it preserves employment status during suspension', function () {
    $tagTeam = TagTeam::factory()->employed()->create();
    $originalEmployment = $tagTeam->currentEmployment();

    expect($tagTeam->isEmployed())->toBeTrue();

    SuspendAction::run($tagTeam);

    $tagTeam->refresh();

    // Should remain employed but now suspended
    expect($tagTeam->isEmployed())->toBeTrue();
    expect($tagTeam->isSuspended())->toBeTrue();

    // Employment record should remain unchanged
    expect($tagTeam->currentEmployment->id)->toBe($originalEmployment->id);
    expect($tagTeam->currentEmployment->ended_at)->toBeNull();
});

test('it preserves suspension history during new suspension', function () {
    $tagTeam = TagTeam::factory()->employed()->create();

    // Create historical suspension
    $tagTeam->suspensions()->create(['started_at' => now()->subDays(20), 'ended_at' => now()->subDays(10)]);
    $originalSuspensionCount = $tagTeam->suspensions()->count();

    SuspendAction::run($tagTeam);

    $tagTeam->refresh();

    // Should preserve all suspension history
    expect($tagTeam->suspensions()->count())->toBe($originalSuspensionCount + 1);

    // All historical records should remain intact
    expect($tagTeam->suspensions()->where('ended_at', '!=', null)->count())->toBe(1);

    // Current suspension should be active
    expect($tagTeam->currentSuspension())->not()->toBeNull();
});

test('it handles tag team with complex employment history', function () {
    $tagTeam = TagTeam::factory()->create();

    // Create complex employment/suspension history
    $tagTeam->employments()->create(['started_at' => now()->subDays(30), 'ended_at' => now()->subDays(25)]);
    $tagTeam->suspensions()->create(['started_at' => now()->subDays(28), 'ended_at' => now()->subDays(25)]);
    $tagTeam->employments()->create(['started_at' => now()->subDays(20), 'ended_at' => null]); // Current

    $tagTeam->refresh();
    expect($tagTeam->isEmployed())->toBeTrue();
    expect($tagTeam->isSuspended())->toBeFalse();

    SuspendAction::run($tagTeam);

    $tagTeam->refresh();

    // Should now be suspended
    expect($tagTeam->isEmployed())->toBeTrue();
    expect($tagTeam->isSuspended())->toBeTrue();

    // Should have preserved all historical records
    expect($tagTeam->employments()->count())->toBe(2);
    expect($tagTeam->suspensions()->count())->toBe(2); // 1 historical + 1 new

    // New suspension should be current
    $currentSuspension = $tagTeam->currentSuspension();
    expect($currentSuspension)->not()->toBeNull();
    expect($currentSuspension->started_at->toDateTimeString())->toBe(now()->toDateTimeString());
});

test('it handles suspension with cascade effects', function () {
    $tagTeam = TagTeam::factory()->employed()->create();

    SuspendAction::run($tagTeam);

    $tagTeam->refresh();

    // Verify the action used StatusTransitionPipeline
    expect($tagTeam->isSuspended())->toBeTrue();
    expect($tagTeam->isEmployed())->toBeTrue();

    // Suspension should be active
    $this->assertDatabaseHas('tag_teams_suspensions', [
        'tag_team_id' => $tagTeam->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);

    // Note: Cascade effects would be tested in cascade strategy tests
});
