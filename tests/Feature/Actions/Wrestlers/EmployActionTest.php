<?php

use App\Actions\Wrestlers\EmployAction;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeEmployedException;
use App\Models\Wrestler;

test('it employs an employable wrestler and redirects', function ($factoryState) {
    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    EmployAction::run($wrestler);

    expect($wrestler->fresh())
        ->employments->toHaveCount(1)
        ->status->toMatchObject(WrestlerStatus::BOOKABLE);
})->with([
    'unemployed',
    'withFutureEmployment',
    'released',
]);

test('invoke throws exception for employing a non employable wrestler', function ($factoryState) {
    $this->withoutExceptionHandling();

    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    EmployAction::run($wrestler);
})->throws(CannotBeEmployedException::class)->with([
    'suspended',
    'injured',
    'bookable',
    'retired',
]);
