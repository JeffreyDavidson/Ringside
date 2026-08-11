<?php

declare(strict_types=1);

use App\Actions\Stables\UpdateAction;
use App\Data\Stables\StableData;
use App\Data\Stables\StableMembershipData;
use App\Exceptions\Lifecycle\InvalidDateRangeException;
use App\Models\Stables\Stable;

test('it rejects an activity end date before the start date', function () {
    $stable = Stable::factory()->inactive()->create(['name' => 'Original Name']);
    $originalPeriod = $stable->firstActivityPeriod()->firstOrFail();
    $startedAt = now()->subMonth();

    $data = new StableData(
        name: 'Updated Name',
        start_date: $startedAt,
        members: new StableMembershipData(),
        end_date: $startedAt->copy()->subSecond(),
    );

    expect(fn () => resolve(UpdateAction::class)->handle($stable, $data))
        ->toThrow(InvalidDateRangeException::class);

    expect($stable->refresh()->name)->toBe('Original Name')
        ->and($originalPeriod->refresh()->started_at->toDateTimeString())
        ->toBe($originalPeriod->started_at->toDateTimeString());
});
