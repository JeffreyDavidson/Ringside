<?php

use App\Actions\Referees\EmployAction;
use App\Enums\RefereeStatus;
use App\Models\Referee;

test('invoke employs an unemployed referee and redirects', function () {
    $referee = Referee::factory()->unemployed()->create();

    EmployAction::run($referee);

    expect($referee->fresh())
        ->employments->toHaveCount(1)
        ->status->toMatchObject(RefereeStatus::BOOKABLE);
});

test('invoke employs a future employed referee and redirects', function () {
    $referee = Referee::factory()->withFutureEmployment()->create();
    $startDate = $referee->employments->first()->started_at;

    EmployAction::run($referee);

    expect($referee->fresh())
        ->currentEmployment->started_at->toBeLessThan($startDate)
        ->status->toMatchObject(RefereeStatus::BOOKABLE);
});

test('invoke employs a released referee and redirects', function () {
    $referee = Referee::factory()->released()->create();

    EmployAction::run($referee);

    expect($referee->fresh())
        ->employments->toHaveCount(2)
        ->status->toMatchObject(RefereeStatus::BOOKABLE);
});
