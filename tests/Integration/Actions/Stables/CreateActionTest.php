<?php

declare(strict_types=1);

use App\Actions\Stables\CreateAction;
use App\Data\Stables\StableData;
use App\Data\Stables\StableMembershipData;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Stables\StableStatus;
use App\Models\Roster\Wrestlers\Wrestler;

test('it creates an unformed stable without a start date', function (): void {
    $data = new StableData(
        name: '  The Alliance  ',
        start_date: null,
        members: new StableMembershipData(),
    );

    $stable = resolve(CreateAction::class)->handle($data);

    expect($stable->name)->toBe('The Alliance')
        ->and($stable->status)->toBe(StableStatus::Unformed)
        ->and($stable->activityPeriods()->exists())->toBeFalse()
        ->and($stable->wrestlers()->exists())->toBeFalse();
});

test('it creates and establishes a stable with founding members', function (): void {
    $wrestlers = Wrestler::factory()->employed()->count(3)->create();
    $startDate = now()->subMonth();

    $data = new StableData(
        name: 'The Alliance',
        start_date: $startDate,
        members: new StableMembershipData(wrestlers: $wrestlers),
    );

    $stable = resolve(CreateAction::class)->handle($data);
    $stable->refresh();

    expect($stable->status)->toBe(StableStatus::Active)
        ->and($stable->wrestlers()->count())->toBe(3)
        ->and($stable->currentActivityPeriod()->exists())->toBeTrue()
        ->and($stable->lifecycleTransitions()->sole()->transition)->toBe(LifecycleTransitionType::Established);

    $membership = $stable->wrestlers()->firstOrFail()->pivot;
    $activityPeriod = $stable->activityPeriods()->firstOrFail();

    expect(requiredDate($membership->joined_at)->toDateTimeString())->toBe($startDate->toDateTimeString())
        ->and(requiredDate($activityPeriod->started_at)->toDateTimeString())->toBe($startDate->toDateTimeString());
});
