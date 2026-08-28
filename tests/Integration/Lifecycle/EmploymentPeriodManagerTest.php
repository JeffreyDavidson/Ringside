<?php

declare(strict_types=1);

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleOwnerType;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\Periods\EmploymentPeriodManager;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it starts an employment period on the effective date', function () {
    $wrestler = Wrestler::factory()->unemployed()->create();
    $effectiveDate = now()->subDay();

    resolve(EmploymentPeriodManager::class)->start(
        $wrestler,
        $effectiveDate,
        LifecycleTransitionType::Employed,
    );

    $this->assertDatabaseHas('employments', [
        'employable_id' => $wrestler->id,
        'employable_type' => $wrestler->getMorphClass(),
        'started_at' => $effectiveDate->toDateTimeString(),
        'ended_at' => null,
    ]);

    $transition = $wrestler->lifecycleTransitions()->sole();
    expect($transition->dimension)->toBe(LifecycleDimension::Employment)
        ->and($transition->transition)->toBe(LifecycleTransitionType::Employed)
        ->and($transition->effective_at->toDateTimeString())->toBe($effectiveDate->toDateTimeString());
});

test('it ends and preserves the active employment period', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    $effectiveDate = now()->subHour();
    $employmentId = $wrestler->currentEmployment()->firstOrFail()->id;

    resolve(EmploymentPeriodManager::class)->end(
        $wrestler,
        $effectiveDate,
        LifecycleTransitionType::Released,
    );

    $this->assertDatabaseHas('employments', [
        'id' => $employmentId,
        'employable_id' => $wrestler->id,
        'employable_type' => $wrestler->getMorphClass(),
        'ended_at' => $effectiveDate->toDateTimeString(),
    ]);

    $transition = $wrestler->lifecycleTransitions()->sole();
    expect($transition->dimension)->toBe(LifecycleDimension::Employment)
        ->and($transition->transition)->toBe(LifecycleTransitionType::Released)
        ->and($transition->effective_at->toDateTimeString())->toBe($effectiveDate->toDateTimeString());
});

test('it records employment transitions for every employable owner', function (LifecycleOwnerType $ownerType) {
    $owner = match ($ownerType) {
        LifecycleOwnerType::Manager => Manager::factory()->create(),
        LifecycleOwnerType::Referee => Referee::factory()->create(),
        LifecycleOwnerType::TagTeam => TagTeam::factory()->create(),
        LifecycleOwnerType::Wrestler => Wrestler::factory()->create(),
        default => throw new LogicException("{$ownerType->value} does not support employment"),
    };
    $effectiveDate = now()->subDay();

    resolve(EmploymentPeriodManager::class)->start(
        $owner,
        $effectiveDate,
        LifecycleTransitionType::Employed,
    );

    $transition = $owner->lifecycleTransitions()->sole();
    expect($transition->subject->is($owner))->toBeTrue()
        ->and($transition->dimension)->toBe(LifecycleDimension::Employment)
        ->and($transition->transition)->toBe(LifecycleTransitionType::Employed);
})->with([
    LifecycleOwnerType::Manager,
    LifecycleOwnerType::Referee,
    LifecycleOwnerType::TagTeam,
    LifecycleOwnerType::Wrestler,
]);
