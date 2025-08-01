<?php

declare(strict_types=1);

use App\Actions\Referees\InjureAction;
use App\Exceptions\Roster\CannotBeInjuredException;
use App\Models\Referees\Referee;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it injures an employed referee', function () {
    $referee = Referee::factory()->employed()->create();

    expect($referee->isEmployed())->toBeTrue();
    expect($referee->isInjured())->toBeFalse();

    InjureAction::run($referee);

    $referee->refresh();
    expect($referee->isInjured())->toBeTrue();

    $this->assertDatabaseHas('referees_injuries', [
        'referee_id' => $referee->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it injures referee with specific injury date', function () {
    $referee = Referee::factory()->employed()->create();
    $injuryDate = now()->subDays(3);

    InjureAction::run($referee, $injuryDate);

    $referee->refresh();
    expect($referee->isInjured())->toBeTrue();

    $this->assertDatabaseHas('referees_injuries', [
        'referee_id' => $referee->id,
        'started_at' => $injuryDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it handles DateHelper date resolution', function () {
    $referee = Referee::factory()->employed()->create();
    $injuryDate = now()->subDays(7);

    InjureAction::run($referee, $injuryDate);

    $referee->refresh();

    // DateHelper should have processed the injury date
    $this->assertDatabaseHas('referees_injuries', [
        'referee_id' => $referee->id,
        'started_at' => $injuryDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it validates referee can be injured', function () {
    $referee = Referee::factory()->employed()->create();

    // Should succeed without throwing validation exception
    InjureAction::run($referee);

    $referee->refresh();
    expect($referee->isInjured())->toBeTrue();
});

test('it throws exception when referee cannot be injured', function () {
    $referee = Referee::factory()->create(); // Not employed

    expect($referee->isEmployed())->toBeFalse();

    expect(fn () => InjureAction::run($referee))
        ->toThrow(CannotBeInjuredException::class);
});

test('it maintains transaction boundaries', function () {
    $referee = Referee::factory()->employed()->create();

    InjureAction::run($referee);

    $referee->refresh();

    // Injury creation should be atomic
    expect($referee->isInjured())->toBeTrue();

    $this->assertDatabaseHas('referees_injuries', [
        'referee_id' => $referee->id,
        'started_at' => now()->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it maintains referee employment after injury', function () {
    $referee = Referee::factory()->employed()->create();
    $employment = $referee->currentEmployment;

    expect($referee->isEmployed())->toBeTrue();

    InjureAction::run($referee);

    $referee->refresh();
    $employment->refresh();

    // Should remain employed after injury
    expect($referee->isEmployed())->toBeTrue();
    expect($referee->isInjured())->toBeTrue();
    expect($employment->ended_at)->toBeNull();
});

test('it creates injury record with correct structure', function () {
    $referee = Referee::factory()->employed()->create();
    $injuryDate = now()->subDays(2);

    InjureAction::run($referee, $injuryDate);

    $injury = $referee->fresh()->currentInjury;

    expect($injury)->not->toBeNull();
    expect($injury->referee_id)->toBe($referee->id);
    expect($injury->started_at->eq($injuryDate))->toBeTrue();
    expect($injury->ended_at)->toBeNull();
});
