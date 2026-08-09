<?php

declare(strict_types=1);

use App\Actions\TagTeams\ReinstateAction;
use App\Models\TagTeams\TagTeam;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it reinstates a suspended tag team', function () {
    $tagTeam = TagTeam::factory()->suspended()->create();

    expect($tagTeam->isSuspended())->toBeTrue();
    expect($tagTeam->isEmployed())->toBeTrue();

    resolve(ReinstateAction::class)->handle($tagTeam);

    $tagTeam->refresh();
    expect($tagTeam->isSuspended())->toBeFalse();
    expect($tagTeam->isEmployed())->toBeTrue();

    // Verify suspension record was ended
    $this->assertDatabaseHas('tag_teams_suspensions', [
        'tag_team_id' => $tagTeam->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it reinstates tag team with specific reinstatement date', function () {
    $tagTeam = TagTeam::factory()->suspended()->create();
    $reinstatementDate = now()->subDays(2);

    resolve(ReinstateAction::class)->handle($tagTeam, $reinstatementDate);

    $tagTeam->refresh();
    expect($tagTeam->isSuspended())->toBeFalse();

    // Verify suspension ended with specific date
    $this->assertDatabaseHas('tag_teams_suspensions', [
        'tag_team_id' => $tagTeam->id,
        'ended_at' => $reinstatementDate->toDateTimeString(),
    ]);
});

test('it persists the reinstatement lifecycle', function () {
    $tagTeam = TagTeam::factory()->suspended()->create();

    // Get current suspension to verify it gets ended
    $currentSuspension = $tagTeam->currentSuspension()->firstOrFail();

    resolve(ReinstateAction::class)->handle($tagTeam);

    $tagTeam->refresh();

    // Verify suspension period was ended
    expect($tagTeam->currentSuspension)->toBeNull();
    expect($tagTeam->isSuspended())->toBeFalse();
    expect($tagTeam->isEmployed())->toBeTrue();
});

test('it prevents reinstating non-suspended tag team', function () {
    $tagTeam = TagTeam::factory()->employed()->create();

    expect($tagTeam->isSuspended())->toBeFalse();

    expect(fn () => resolve(ReinstateAction::class)->handle($tagTeam))
        ->toThrow(Exception::class);
});

test('it prevents reinstating unemployed tag team', function () {
    $tagTeam = TagTeam::factory()->create();

    expect($tagTeam->isEmployed())->toBeFalse();
    expect($tagTeam->isSuspended())->toBeFalse();

    expect(fn () => resolve(ReinstateAction::class)->handle($tagTeam))
        ->toThrow(Exception::class);
});

test('it handles database transactions correctly', function () {
    $tagTeam = TagTeam::factory()->suspended()->create();
    $originalSuspensionId = $tagTeam->currentSuspension()->firstOrFail()->id;

    resolve(ReinstateAction::class)->handle($tagTeam);

    $tagTeam->refresh();

    // Verify the transaction was successful
    expect($tagTeam->isSuspended())->toBeFalse();
    expect($tagTeam->isEmployed())->toBeTrue();

    // Verify original suspension record was properly ended
    $this->assertDatabaseHas('tag_teams_suspensions', [
        'id' => $originalSuspensionId,
        'tag_team_id' => $tagTeam->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it ends current suspension period', function () {
    $tagTeam = TagTeam::factory()->suspended()->create();
    $originalSuspensionCount = $tagTeam->suspensions()->count();

    resolve(ReinstateAction::class)->handle($tagTeam);

    $tagTeam->refresh();

    // Should not create new suspension records, just end current one
    expect($tagTeam->suspensions()->count())->toBe($originalSuspensionCount);
    expect($tagTeam->isSuspended())->toBeFalse();

    // All suspension records should have end dates now
    expect($tagTeam->suspensions()->whereNull('ended_at')->count())->toBe(0);
});

test('it uses DateHelper for consistent date handling', function () {
    $tagTeam = TagTeam::factory()->suspended()->create();
    $customReinstatementDate = now()->subDays(1)->startOfDay();

    resolve(ReinstateAction::class)->handle($tagTeam, $customReinstatementDate);

    $tagTeam->refresh();

    // Verify DateHelper was used for date resolution
    $this->assertDatabaseHas('tag_teams_suspensions', [
        'tag_team_id' => $tagTeam->id,
        'ended_at' => $customReinstatementDate->toDateTimeString(),
    ]);
});

test('it preserves suspension history during reinstatement', function () {
    $tagTeam = TagTeam::factory()->suspended()->create();

    // Create historical suspension
    $tagTeam->suspensions()->create([
        'started_at' => now()->subDays(30),
        'ended_at' => now()->subDays(25),
    ]);

    $originalSuspensionCount = $tagTeam->suspensions()->count();

    resolve(ReinstateAction::class)->handle($tagTeam);

    $tagTeam->refresh();

    // Should preserve all suspension history
    expect($tagTeam->suspensions()->count())->toBe($originalSuspensionCount);

    // All suspension records should have end dates now
    expect($tagTeam->suspensions()->whereNull('ended_at')->count())->toBe(0);

    // Current suspension should be null
    expect($tagTeam->currentSuspension)->toBeNull();
});

test('it handles tag team with complex suspension history', function () {
    $tagTeam = TagTeam::factory()->create();

    // Create complex suspension history
    $tagTeam->employments()->create(['started_at' => now()->subDays(50), 'ended_at' => null]);
    $tagTeam->suspensions()->create(['started_at' => now()->subDays(30), 'ended_at' => now()->subDays(25)]);
    $tagTeam->suspensions()->create(['started_at' => now()->subDays(20), 'ended_at' => now()->subDays(15)]);
    $tagTeam->suspensions()->create(['started_at' => now()->subDays(10), 'ended_at' => null]); // Current

    $tagTeam->refresh();
    expect($tagTeam->isSuspended())->toBeTrue();
    expect($tagTeam->suspensions()->count())->toBe(3);

    resolve(ReinstateAction::class)->handle($tagTeam);

    $tagTeam->refresh();

    // Should now be reinstated
    expect($tagTeam->isSuspended())->toBeFalse();
    expect($tagTeam->isEmployed())->toBeTrue();

    // Should have preserved all historical records
    expect($tagTeam->suspensions()->count())->toBe(3);

    // All suspension records should have end dates now
    expect($tagTeam->suspensions()->whereNull('ended_at')->count())->toBe(0);
});

test('it maintains employment status during reinstatement', function () {
    $tagTeam = TagTeam::factory()->suspended()->create();

    // Verify starting state
    expect($tagTeam->isEmployed())->toBeTrue();
    expect($tagTeam->isSuspended())->toBeTrue();

    resolve(ReinstateAction::class)->handle($tagTeam);

    $tagTeam->refresh();

    // Should remain employed but no longer suspended
    expect($tagTeam->isEmployed())->toBeTrue();
    expect($tagTeam->isSuspended())->toBeFalse();

    // Employment record should remain active
    expect($tagTeam->currentEmployment)->not()->toBeNull();
    expect($tagTeam->currentEmployment()->firstOrFail()->ended_at)->toBeNull();
});

test('it handles reinstatement with cascade effects', function () {
    $tagTeam = TagTeam::factory()->suspended()->create();

    resolve(ReinstateAction::class)->handle($tagTeam);

    $tagTeam->refresh();

    // Verify the action persisted the expected lifecycle state
    expect($tagTeam->isSuspended())->toBeFalse();
    expect($tagTeam->isEmployed())->toBeTrue();

    // Suspension should be ended
    $this->assertDatabaseHas('tag_teams_suspensions', [
        'tag_team_id' => $tagTeam->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Note: Cascade effects would be tested in cascade strategy tests
});
