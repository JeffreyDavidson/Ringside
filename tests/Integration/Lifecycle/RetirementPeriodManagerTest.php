<?php

declare(strict_types=1);

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleOwnerType;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\RetirementPeriodManager;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it starts a retirement period on the effective date', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    $effectiveDate = now()->subDay();

    resolve(RetirementPeriodManager::class)->start(
        $wrestler,
        $effectiveDate,
        LifecycleTransitionType::Retired,
    );

    $this->assertDatabaseHas('retirements', [
        'retirable_id' => $wrestler->id,
        'retirable_type' => $wrestler->getMorphClass(),
        'started_at' => $effectiveDate->toDateTimeString(),
        'ended_at' => null,
    ]);

    $transition = $wrestler->lifecycleTransitions()->sole();
    expect($transition->dimension)->toBe(LifecycleDimension::Retirement)
        ->and($transition->transition)->toBe(LifecycleTransitionType::Retired)
        ->and($transition->effective_at->toDateTimeString())->toBe($effectiveDate->toDateTimeString());
});

test('it ends and preserves the active retirement period', function () {
    $wrestler = Wrestler::factory()->retired()->create();
    $effectiveDate = now()->subHour();
    $retirementId = $wrestler->currentRetirement()->firstOrFail()->id;

    resolve(RetirementPeriodManager::class)->end(
        $wrestler,
        $effectiveDate,
        LifecycleTransitionType::Unretired,
    );

    $this->assertDatabaseHas('retirements', [
        'id' => $retirementId,
        'retirable_id' => $wrestler->id,
        'retirable_type' => $wrestler->getMorphClass(),
        'ended_at' => $effectiveDate->toDateTimeString(),
    ]);

    $transition = $wrestler->lifecycleTransitions()->sole();
    expect($transition->dimension)->toBe(LifecycleDimension::Retirement)
        ->and($transition->transition)->toBe(LifecycleTransitionType::Unretired)
        ->and($transition->effective_at->toDateTimeString())->toBe($effectiveDate->toDateTimeString());
});

test('it records retirement transitions for every supported owner', function (LifecycleOwnerType $ownerType) {
    $owner = match ($ownerType) {
        LifecycleOwnerType::Manager => Manager::factory()->create(),
        LifecycleOwnerType::Referee => Referee::factory()->create(),
        LifecycleOwnerType::Stable => Stable::factory()->create(),
        LifecycleOwnerType::TagTeam => TagTeam::factory()->create(),
        LifecycleOwnerType::Title => Title::factory()->create(),
        LifecycleOwnerType::Wrestler => Wrestler::factory()->create(),
        default => throw new LogicException("{$ownerType->value} does not support retirement"),
    };
    $effectiveDate = now()->subDay();

    resolve(RetirementPeriodManager::class)->start(
        $owner,
        $effectiveDate,
        LifecycleTransitionType::Retired,
    );

    $transition = $owner->lifecycleTransitions()->sole();
    expect($transition->subject->is($owner))->toBeTrue()
        ->and($transition->dimension)->toBe(LifecycleDimension::Retirement)
        ->and($transition->transition)->toBe(LifecycleTransitionType::Retired);
})->with([
    LifecycleOwnerType::Manager,
    LifecycleOwnerType::Referee,
    LifecycleOwnerType::Stable,
    LifecycleOwnerType::TagTeam,
    LifecycleOwnerType::Title,
    LifecycleOwnerType::Wrestler,
]);
