<?php

declare(strict_types=1);

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleOwnerType;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\InjuryPeriodManager;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it starts an injury period on the effective date', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    $effectiveDate = now()->subDay();

    resolve(InjuryPeriodManager::class)->start($wrestler, $effectiveDate, LifecycleTransitionType::Injured);

    $this->assertDatabaseHas('injuries', [
        'injurable_id' => $wrestler->id,
        'injurable_type' => $wrestler->getMorphClass(),
        'started_at' => $effectiveDate->toDateTimeString(),
        'ended_at' => null,
    ]);

    $transition = $wrestler->lifecycleTransitions()->sole();
    expect($transition->dimension)->toBe(LifecycleDimension::Injury)
        ->and($transition->transition)->toBe(LifecycleTransitionType::Injured)
        ->and($transition->effective_at->toDateTimeString())->toBe($effectiveDate->toDateTimeString());
});

test('it ends and preserves the active injury period', function () {
    $wrestler = Wrestler::factory()->injured()->create();
    $effectiveDate = now()->subHour();
    $injuryId = $wrestler->currentInjury()->firstOrFail()->id;

    resolve(InjuryPeriodManager::class)->end($wrestler, $effectiveDate, LifecycleTransitionType::Healed);

    $this->assertDatabaseHas('injuries', [
        'id' => $injuryId,
        'injurable_id' => $wrestler->id,
        'injurable_type' => $wrestler->getMorphClass(),
        'ended_at' => $effectiveDate->toDateTimeString(),
    ]);

    $transition = $wrestler->lifecycleTransitions()->sole();
    expect($transition->dimension)->toBe(LifecycleDimension::Injury)
        ->and($transition->transition)->toBe(LifecycleTransitionType::Healed)
        ->and($transition->effective_at->toDateTimeString())->toBe($effectiveDate->toDateTimeString());
});

test('it records injury transitions for every injurable owner', function (LifecycleOwnerType $ownerType) {
    $owner = match ($ownerType) {
        LifecycleOwnerType::Manager => Manager::factory()->employed()->create(),
        LifecycleOwnerType::Referee => Referee::factory()->employed()->create(),
        LifecycleOwnerType::Wrestler => Wrestler::factory()->employed()->create(),
        default => throw new LogicException("{$ownerType->value} does not support injury"),
    };

    resolve(InjuryPeriodManager::class)->start($owner, now(), LifecycleTransitionType::Injured);

    $transition = $owner->lifecycleTransitions()
        ->where('dimension', LifecycleDimension::Injury)
        ->sole();
    expect($transition->subject->is($owner))->toBeTrue()
        ->and($transition->transition)->toBe(LifecycleTransitionType::Injured);
})->with([
    LifecycleOwnerType::Manager,
    LifecycleOwnerType::Referee,
    LifecycleOwnerType::Wrestler,
]);

test('it closes an injury without inventing a healing transition', function () {
    $wrestler = Wrestler::factory()->injured()->create();

    resolve(InjuryPeriodManager::class)->end($wrestler, now());

    expect($wrestler->lifecycleTransitions()->count())->toBe(0);
});
