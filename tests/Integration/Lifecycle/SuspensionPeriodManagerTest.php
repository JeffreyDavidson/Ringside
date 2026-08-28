<?php

declare(strict_types=1);

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleOwnerType;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\Periods\SuspensionPeriodManager;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it starts a suspension period on the effective date', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    $effectiveDate = now()->subDay();

    resolve(SuspensionPeriodManager::class)->start(
        $wrestler,
        $effectiveDate,
        LifecycleTransitionType::Suspended,
    );

    $this->assertDatabaseHas('suspensions', [
        'suspendable_id' => $wrestler->id,
        'suspendable_type' => $wrestler->getMorphClass(),
        'started_at' => $effectiveDate->toDateTimeString(),
        'ended_at' => null,
    ]);

    $transition = $wrestler->lifecycleTransitions()->sole();
    expect($transition->dimension)->toBe(LifecycleDimension::Suspension)
        ->and($transition->transition)->toBe(LifecycleTransitionType::Suspended)
        ->and($transition->effective_at->toDateTimeString())->toBe($effectiveDate->toDateTimeString());
});

test('it ends and preserves the active suspension period', function () {
    $wrestler = Wrestler::factory()->suspended()->create();
    $effectiveDate = now()->subHour();
    $suspensionId = $wrestler->currentSuspension()->firstOrFail()->id;

    resolve(SuspensionPeriodManager::class)->end(
        $wrestler,
        $effectiveDate,
        LifecycleTransitionType::Reinstated,
    );

    $this->assertDatabaseHas('suspensions', [
        'id' => $suspensionId,
        'suspendable_id' => $wrestler->id,
        'suspendable_type' => $wrestler->getMorphClass(),
        'ended_at' => $effectiveDate->toDateTimeString(),
    ]);

    $transition = $wrestler->lifecycleTransitions()->sole();
    expect($transition->dimension)->toBe(LifecycleDimension::Suspension)
        ->and($transition->transition)->toBe(LifecycleTransitionType::Reinstated)
        ->and($transition->effective_at->toDateTimeString())->toBe($effectiveDate->toDateTimeString());
});

test('it records suspension transitions for every suspendable owner', function (LifecycleOwnerType $ownerType) {
    $owner = match ($ownerType) {
        LifecycleOwnerType::Manager => Manager::factory()->employed()->create(),
        LifecycleOwnerType::Referee => Referee::factory()->employed()->create(),
        LifecycleOwnerType::TagTeam => TagTeam::factory()->employed()->create(),
        LifecycleOwnerType::Wrestler => Wrestler::factory()->employed()->create(),
        default => throw new LogicException("{$ownerType->value} does not support suspension"),
    };
    $effectiveDate = now()->subDay();

    resolve(SuspensionPeriodManager::class)->start(
        $owner,
        $effectiveDate,
        LifecycleTransitionType::Suspended,
    );

    $transition = $owner->lifecycleTransitions()
        ->where('dimension', LifecycleDimension::Suspension)
        ->sole();
    expect($transition->subject->is($owner))->toBeTrue()
        ->and($transition->transition)->toBe(LifecycleTransitionType::Suspended);
})->with([
    LifecycleOwnerType::Manager,
    LifecycleOwnerType::Referee,
    LifecycleOwnerType::TagTeam,
    LifecycleOwnerType::Wrestler,
]);

test('it closes a suspension without inventing a reinstatement transition', function () {
    $wrestler = Wrestler::factory()->suspended()->create();

    resolve(SuspensionPeriodManager::class)->end($wrestler, now());

    expect($wrestler->lifecycleTransitions()->count())->toBe(0);
});
