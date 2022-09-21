<?php

test('invoke employs an unemployed referee and redirects', function () {
    $referee = Referee::factory()->unemployed()->create();

    $this->actingAs(administrator())
        ->patch(action([EmployController::class], $referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    expect($referee->fresh())
        ->employments->toHaveCount(1)
        ->status->toMatchObject(RefereeStatus::BOOKABLE);
});

test('invoke employs a future employed referee and redirects', function () {
    $referee = Referee::factory()->withFutureEmployment()->create();
    $startDate = $referee->employments->last()->started_at;

    $this->actingAs(administrator())
        ->patch(action([EmployController::class], $referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    expect($referee->fresh())
        ->currentEmployment->started_at->toBeLessThan($startDate)
        ->status->toMatchObject(RefereeStatus::BOOKABLE);
});

test('invoke employs a released referee and redirects', function () {
    $referee = Referee::factory()->released()->create();

    $this->actingAs(administrator())
        ->patch(action([EmployController::class], $referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    expect($referee->fresh())
        ->employments->toHaveCount(2)
        ->status->toMatchObject(RefereeStatus::BOOKABLE);
});

test('invoke throws exception for injuring a non injurable referee', function ($factoryState) {
    $this->withoutExceptionHandling();

    $referee = Referee::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([EmployController::class], $referee));
})->throws(CannotBeEmployedException::class)->with([
    'suspended',
    'injured',
    'bookable',
    'retired',
]);
