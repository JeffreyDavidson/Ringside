<?php

declare(strict_types=1);

use App\Actions\TagTeams\UnretireAction;
use App\Exceptions\Roster\TagTeams\CannotBeUnretiredException;
use App\Lifecycle\TagTeamRetirementEligibility;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it unretires a retired tag team', function () {
    $tagTeam = TagTeam::factory()->retired()->create();

    expect($tagTeam->isRetired())->toBeTrue();
    expect($tagTeam->isEmployed())->toBeFalse();

    resolve(UnretireAction::class)->handle($tagTeam);

    $tagTeam->refresh();
    expect($tagTeam->isRetired())->toBeFalse();
    expect($tagTeam->isEmployed())->toBeTrue();

    // Verify retirement record was ended
    $this->assertDatabaseHas('retirements', [
        'retirable_id' => $tagTeam->id,
        'retirable_type' => $tagTeam->getMorphClass(),
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Verify employment record was created
    $this->assertDatabaseHas('employments', [
        'employable_id' => $tagTeam->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it prevents unretiring a tag team with an injured current wrestler', function () {
    $tagTeam = TagTeam::factory()->create();
    $wrestlers = Wrestler::factory()->employed()->count(1)->create();
    $injuredWrestler = Wrestler::factory()->injured()->create();

    $tagTeam->retirements()->create([
        'started_at' => now()->subDay(),
        'ended_at' => null,
    ]);
    $tagTeam->wrestlers()->attach(
        $wrestlers->push($injuredWrestler),
        ['joined_at' => now()->subDays(2), 'left_at' => null],
    );

    expect(resolve(TagTeamRetirementEligibility::class)->canUnretire($tagTeam))->toBeFalse();

    expect(fn () => resolve(UnretireAction::class)->handle($tagTeam))
        ->toThrow(CannotBeUnretiredException::class);
});

test('it prevents unretiring a tag team without enough current wrestlers', function () {
    $tagTeam = TagTeam::factory()->create();
    $wrestler = Wrestler::factory()->create();

    $tagTeam->retirements()->create([
        'started_at' => now()->subDay(),
        'ended_at' => null,
    ]);
    $tagTeam->wrestlers()->attach($wrestler, [
        'joined_at' => now()->subDays(2),
        'left_at' => null,
    ]);

    expect(resolve(TagTeamRetirementEligibility::class)->canUnretire($tagTeam))->toBeFalse();

    expect(fn () => resolve(UnretireAction::class)->handle($tagTeam))
        ->toThrow(CannotBeUnretiredException::class);
});

test('it unretires and employs current members by default', function () {
    $tagTeam = TagTeam::factory()->retired()->create();
    $manager = Manager::factory()->retired()->create();
    $wrestlers = $tagTeam->currentWrestlers()->get();

    $tagTeam->managers()->attach($manager, ['hired_at' => now()->subMonth()]);

    resolve(UnretireAction::class)
        ->handle($tagTeam);

    foreach ($wrestlers as $wrestler) {
        $wrestler->refresh();

        expect($wrestler->isRetired())->toBeFalse()
            ->and($wrestler->isEmployed())->toBeTrue();
    }

    $manager->refresh();

    expect($manager->isRetired())->toBeFalse()
        ->and($manager->isEmployed())->toBeTrue();
});

test('it can unretire the tag team without unretiring or employing its members', function () {
    $tagTeam = TagTeam::factory()->retired()->create();
    $wrestlers = $tagTeam->currentWrestlers()->get();

    resolve(UnretireAction::class)
        ->handle($tagTeam, unretireMembers: false, employImmediately: false);

    $tagTeam->refresh();

    expect($tagTeam->isRetired())->toBeFalse()
        ->and($tagTeam->isEmployed())->toBeFalse();

    foreach ($wrestlers as $wrestler) {
        $wrestler->refresh();

        expect($wrestler->isRetired())->toBeTrue()
            ->and($wrestler->isEmployed())->toBeFalse();
    }
});

test('it unretires tag team with specific unretirement date', function () {
    $tagTeam = TagTeam::factory()->retired()->create();
    $unretirementDate = now()->subDays(4);

    resolve(UnretireAction::class)->handle($tagTeam, $unretirementDate);

    $tagTeam->refresh();
    expect($tagTeam->isRetired())->toBeFalse();
    expect($tagTeam->isEmployed())->toBeTrue();

    // Verify retirement ended with specific date
    $this->assertDatabaseHas('retirements', [
        'retirable_id' => $tagTeam->id,
        'retirable_type' => $tagTeam->getMorphClass(),
        'ended_at' => $unretirementDate->toDateTimeString(),
    ]);

    // Verify employment started with same date
    $this->assertDatabaseHas('employments', [
        'employable_id' => $tagTeam->id,
        'started_at' => $unretirementDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it persists the unretirement lifecycle', function () {
    $tagTeam = TagTeam::factory()->retired()->create();

    // Get current retirement to verify it gets ended
    $currentRetirement = $tagTeam->currentRetirement()->firstOrFail();
    expect($tagTeam->currentEmployment)->toBeNull();

    resolve(UnretireAction::class)->handle($tagTeam);

    $tagTeam->refresh();

    // Verify retirement ended and employment was created
    expect($tagTeam->currentRetirement)->toBeNull();
    expect($tagTeam->currentEmployment)->not()->toBeNull();
    expect($tagTeam->isRetired())->toBeFalse();
    expect($tagTeam->isEmployed())->toBeTrue();
});

test('it unretires without auto-employing when no current wrestlers are available', function () {
    $tagTeam = TagTeam::factory()->create();
    $tagTeam->retirements()->create([
        'started_at' => now()->subDays(2),
        'ended_at' => null,
    ]);

    $tagTeam->refresh();
    expect($tagTeam->isRetired())->toBeTrue();
    expect($tagTeam->currentWrestlers)->toBeEmpty();
    expect(resolve(TagTeamRetirementEligibility::class)->canUnretire($tagTeam))->toBeFalse()
        ->and(resolve(TagTeamRetirementEligibility::class)->canUnretire($tagTeam, requireAvailablePartners: false))->toBeTrue();

    resolve(UnretireAction::class)->handle($tagTeam, requireAvailablePartners: false);

    $tagTeam->refresh();

    expect($tagTeam->isRetired())->toBeFalse();
    expect($tagTeam->isEmployed())->toBeFalse();
    expect($tagTeam->currentRetirement)->toBeNull();
    expect($tagTeam->currentEmployment)->toBeNull();
    expect($tagTeam->employments()->count())->toBe(0);
});

test('it prevents unretiring non-retired tag team', function () {
    $tagTeam = TagTeam::factory()->employed()->create();

    expect($tagTeam->isRetired())->toBeFalse();

    expect(fn () => resolve(UnretireAction::class)->handle($tagTeam))
        ->toThrow(Exception::class);
});

test('it prevents unretiring unemployed tag team', function () {
    $tagTeam = TagTeam::factory()->unemployed()->create();

    expect($tagTeam->isEmployed())->toBeFalse();
    expect($tagTeam->isRetired())->toBeFalse();

    expect(fn () => resolve(UnretireAction::class)->handle($tagTeam))
        ->toThrow(Exception::class);
});

test('it handles database transactions correctly', function () {
    $tagTeam = TagTeam::factory()->retired()->create();
    $originalRetirementId = $tagTeam->currentRetirement()->firstOrFail()->id;

    resolve(UnretireAction::class)->handle($tagTeam);

    $tagTeam->refresh();

    // Verify the transition was successful
    expect($tagTeam->isRetired())->toBeFalse();
    expect($tagTeam->isEmployed())->toBeTrue();

    // Verify original retirement record was properly ended
    $this->assertDatabaseHas('retirements', [
        'id' => $originalRetirementId,
        'retirable_id' => $tagTeam->id,
        'retirable_type' => $tagTeam->getMorphClass(),
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Verify new employment record was created
    $employment = $tagTeam->currentEmployment()->firstOrFail();
    expect(requiredDate($employment->started_at)->toDateTimeString())->toBe(now()->toDateTimeString());
    expect($employment->ended_at)->toBeNull();
});

test('it ends current retirement period', function () {
    $tagTeam = TagTeam::factory()->retired()->create();
    $originalRetirementCount = $tagTeam->retirements()->count();

    resolve(UnretireAction::class)->handle($tagTeam);

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

    resolve(UnretireAction::class)->handle($tagTeam);

    $tagTeam->refresh();

    // Should create a new employment record
    expect($tagTeam->employments()->count())->toBe($originalEmploymentCount + 1);
    expect($tagTeam->isEmployed())->toBeTrue();

    // New employment should be current and active
    $currentEmployment = $tagTeam->currentEmployment()->firstOrFail();
    expect(requiredDate($currentEmployment->started_at)->toDateTimeString())->toBe(now()->toDateTimeString());
    expect($currentEmployment->ended_at)->toBeNull();
});

test('it uses DateHelper for consistent date handling', function () {
    $tagTeam = TagTeam::factory()->retired()->create();
    $customUnretirementDate = now()->subDays(2)->startOfDay();

    resolve(UnretireAction::class)->handle($tagTeam, $customUnretirementDate);

    $tagTeam->refresh();

    // Verify DateHelper was used for date resolution across all operations
    $this->assertDatabaseHas('retirements', [
        'retirable_id' => $tagTeam->id,
        'retirable_type' => $tagTeam->getMorphClass(),
        'ended_at' => $customUnretirementDate->toDateTimeString(),
    ]);

    $this->assertDatabaseHas('employments', [
        'employable_id' => $tagTeam->id,
        'started_at' => $customUnretirementDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it handles multiple retirement history correctly', function () {
    $tagTeam = TagTeam::factory()->unemployed()->create();

    // Create multiple retirement history
    $tagTeam->retirements()->create(['started_at' => now()->subDays(40), 'ended_at' => now()->subDays(35)]);
    $tagTeam->retirements()->create(['started_at' => now()->subDays(30), 'ended_at' => now()->subDays(25)]);
    $tagTeam->retirements()->create(['started_at' => now()->subDays(20), 'ended_at' => null]); // Current

    $tagTeam->refresh();
    expect($tagTeam->isRetired())->toBeTrue();
    expect($tagTeam->retirements()->count())->toBe(3);

    resolve(UnretireAction::class)->handle($tagTeam);

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
    $tagTeam = TagTeam::factory()->unemployed()->create();

    // Create complex history
    $tagTeam->employments()->create(['started_at' => now()->subDays(50), 'ended_at' => now()->subDays(45)]);
    $tagTeam->retirements()->create(['started_at' => now()->subDays(45), 'ended_at' => now()->subDays(40)]);
    $tagTeam->employments()->create(['started_at' => now()->subDays(40), 'ended_at' => now()->subDays(35)]);
    $tagTeam->retirements()->create(['started_at' => now()->subDays(35), 'ended_at' => null]); // Current

    $originalEmploymentCount = $tagTeam->employments()->count();
    $originalRetirementCount = $tagTeam->retirements()->count();

    resolve(UnretireAction::class)->handle($tagTeam);

    $tagTeam->refresh();

    // Should preserve all historical records
    expect($tagTeam->employments()->count())->toBe($originalEmploymentCount + 1);
    expect($tagTeam->retirements()->count())->toBe($originalRetirementCount);

    // Current retirement should be ended, current employment should be active
    expect($tagTeam->currentRetirement)->toBeNull();
    expect($tagTeam->currentEmployment)->not()->toBeNull();
});

test('it handles unretirement with cascade effects', function () {
    $tagTeam = TagTeam::factory()->retired()->create();

    resolve(UnretireAction::class)->handle($tagTeam);

    $tagTeam->refresh();

    // Verify the selected cascade preserved the expected lifecycle state
    expect($tagTeam->isRetired())->toBeFalse();
    expect($tagTeam->isEmployed())->toBeTrue();

    // Retirement should be ended
    $this->assertDatabaseHas('retirements', [
        'retirable_id' => $tagTeam->id,
        'retirable_type' => $tagTeam->getMorphClass(),
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Employment should be active
    $this->assertDatabaseHas('employments', [
        'employable_id' => $tagTeam->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);

});

test('it transitions from retired to employed seamlessly', function () {
    $tagTeam = TagTeam::factory()->retired()->create();

    // Verify starting state
    expect($tagTeam->isRetired())->toBeTrue();
    expect($tagTeam->isEmployed())->toBeFalse();
    expect($tagTeam->isSuspended())->toBeFalse();

    resolve(UnretireAction::class)->handle($tagTeam);

    $tagTeam->refresh();

    // Should transition to employed state
    expect($tagTeam->isRetired())->toBeFalse();
    expect($tagTeam->isEmployed())->toBeTrue();
    expect($tagTeam->isSuspended())->toBeFalse();

    // Should have active employment and no active retirement
    expect($tagTeam->currentEmployment)->not()->toBeNull();
    expect($tagTeam->currentRetirement)->toBeNull();
});
