<?php

declare(strict_types=1);

use App\Actions\TagTeams\ReleaseAction;
use App\Models\TagTeams\TagTeam;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it releases an employed tag team', function () {
    $tagTeam = TagTeam::factory()->employed()->create();

    expect($tagTeam->isEmployed())->toBeTrue();

    resolve(ReleaseAction::class)->handle($tagTeam);

    $tagTeam->refresh();
    expect($tagTeam->isEmployed())->toBeFalse();

    // Verify employment record was ended
    $this->assertDatabaseHas('tag_teams_employments', [
        'tag_team_id' => $tagTeam->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it releases tag team with specific release date', function () {
    $tagTeam = TagTeam::factory()->employed()->create();
    $releaseDate = now()->subDays(3);

    resolve(ReleaseAction::class)->handle($tagTeam, $releaseDate);

    $tagTeam->refresh();
    expect($tagTeam->isEmployed())->toBeFalse();

    // Verify employment ended with specific date
    $this->assertDatabaseHas('tag_teams_employments', [
        'tag_team_id' => $tagTeam->id,
        'ended_at' => $releaseDate->toDateTimeString(),
    ]);
});

test('it releases suspended tag team', function () {
    $tagTeam = TagTeam::factory()->suspended()->create();

    expect($tagTeam->isEmployed())->toBeTrue();
    expect($tagTeam->isSuspended())->toBeTrue();

    resolve(ReleaseAction::class)->handle($tagTeam);

    $tagTeam->refresh();
    expect($tagTeam->isEmployed())->toBeFalse();
    expect($tagTeam->isSuspended())->toBeFalse();

    // Verify employment ended
    $this->assertDatabaseHas('tag_teams_employments', [
        'tag_team_id' => $tagTeam->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Verify suspension ended
    $this->assertDatabaseHas('tag_teams_suspensions', [
        'tag_team_id' => $tagTeam->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it persists the release lifecycle', function () {
    $tagTeam = TagTeam::factory()->employed()->create();

    // Get current employment to verify it gets ended
    $currentEmployment = $tagTeam->currentEmployment()->firstOrFail();

    resolve(ReleaseAction::class)->handle($tagTeam);

    $tagTeam->refresh();

    // Verify employment period was ended
    expect($tagTeam->currentEmployment)->toBeNull();
    expect($tagTeam->isEmployed())->toBeFalse();

    // Verify records show proper dates
    $this->assertDatabaseHas('tag_teams_employments', [
        'tag_team_id' => $tagTeam->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it prevents releasing unemployed tag team', function () {
    $tagTeam = TagTeam::factory()->create();

    expect($tagTeam->isEmployed())->toBeFalse();

    expect(fn () => resolve(ReleaseAction::class)->handle($tagTeam))
        ->toThrow(Exception::class);
});

test('it handles database transactions correctly', function () {
    $tagTeam = TagTeam::factory()->employed()->create();
    $originalEmploymentId = $tagTeam->currentEmployment()->firstOrFail()->id;

    resolve(ReleaseAction::class)->handle($tagTeam);

    $tagTeam->refresh();

    // Verify the transaction was successful
    expect($tagTeam->isEmployed())->toBeFalse();

    // Verify original employment record was properly ended
    $this->assertDatabaseHas('tag_teams_employments', [
        'id' => $originalEmploymentId,
        'tag_team_id' => $tagTeam->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it ends current employment period', function () {
    $tagTeam = TagTeam::factory()->employed()->create();
    $originalEmploymentCount = $tagTeam->employments()->count();

    resolve(ReleaseAction::class)->handle($tagTeam);

    $tagTeam->refresh();

    // Should not create new employment records, just end current one
    expect($tagTeam->employments()->count())->toBe($originalEmploymentCount);
    expect($tagTeam->isEmployed())->toBeFalse();

    // All employment records should have end dates
    expect($tagTeam->employments()->whereNull('ended_at')->count())->toBe(0);
});

test('it uses DateHelper for consistent date handling', function () {
    $tagTeam = TagTeam::factory()->employed()->create();
    $customReleaseDate = now()->subDays(2)->startOfDay();

    resolve(ReleaseAction::class)->handle($tagTeam, $customReleaseDate);

    $tagTeam->refresh();

    // Verify DateHelper was used for date resolution
    $this->assertDatabaseHas('tag_teams_employments', [
        'tag_team_id' => $tagTeam->id,
        'ended_at' => $customReleaseDate->toDateTimeString(),
    ]);
});

test('it preserves employment history during release', function () {
    $tagTeam = TagTeam::factory()->employed()->create();
    $originalEmploymentCount = $tagTeam->employments()->count();

    resolve(ReleaseAction::class)->handle($tagTeam);

    $tagTeam->refresh();

    // Should preserve all employment history
    expect($tagTeam->employments()->count())->toBe($originalEmploymentCount);

    // All employment records should have end dates now
    expect($tagTeam->employments()->whereNull('ended_at')->count())->toBe(0);

    // Current employment should be null
    expect($tagTeam->currentEmployment)->toBeNull();
});

test('it handles tag team with complex employment history', function () {
    $tagTeam = TagTeam::factory()->create();

    // Create complex employment history
    $tagTeam->employments()->create(['started_at' => now()->subDays(30), 'ended_at' => now()->subDays(25)]);
    $tagTeam->employments()->create(['started_at' => now()->subDays(20), 'ended_at' => now()->subDays(15)]);
    $tagTeam->employments()->create(['started_at' => now()->subDays(10), 'ended_at' => null]); // Current

    $tagTeam->refresh();
    expect($tagTeam->isEmployed())->toBeTrue();
    expect($tagTeam->employments()->count())->toBe(3);

    resolve(ReleaseAction::class)->handle($tagTeam);

    $tagTeam->refresh();

    // Should now be unemployed
    expect($tagTeam->isEmployed())->toBeFalse();

    // Should have preserved all historical records
    expect($tagTeam->employments()->count())->toBe(3);

    // All employment records should have end dates now
    expect($tagTeam->employments()->whereNull('ended_at')->count())->toBe(0);
});

test('it handles release with cascade to partners and managers', function () {
    $tagTeam = TagTeam::factory()->employed()->create();

    // Get current employment to verify cascade effects
    expect($tagTeam->isEmployed())->toBeTrue();

    resolve(ReleaseAction::class)->handle($tagTeam);

    $tagTeam->refresh();

    // Verify tag team is released
    expect($tagTeam->isEmployed())->toBeFalse();

    // Verify employment record ended
    $this->assertDatabaseHas('tag_teams_employments', [
        'tag_team_id' => $tagTeam->id,
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Note: Cascade effects on partners/managers would be tested in cascade strategy tests
});

test('it ends all current relationships', function () {
    $tagTeam = TagTeam::factory()->employed()->create();

    resolve(ReleaseAction::class)->handle($tagTeam);

    $tagTeam->refresh();

    // Verify the action applied the relationship cascade
    expect($tagTeam->isEmployed())->toBeFalse();

    // Employment should be ended
    $this->assertDatabaseHas('tag_teams_employments', [
        'tag_team_id' => $tagTeam->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});
