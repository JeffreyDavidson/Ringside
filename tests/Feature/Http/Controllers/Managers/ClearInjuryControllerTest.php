<?php

use App\Enums\ManagerStatus;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Http\Controllers\Managers\ClearInjuryController;
use App\Http\Controllers\Managers\ManagersController;
use App\Models\Manager;

beforeEach(function () {
    $this->manager = Manager::factory()->injured()->create();
});

test('invoke marks an injured manager as being cleared from injury and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([ClearInjuryController::class], $this->manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    expect($this->manager->fresh())
        ->injuries->last()->ended_at->not->toBeNull()
        ->status->toBe(ManagerStatus::AVAILABLE);
});

test('a basic user cannot mark an injured manager as cleared', function () {
    $this->actingAs(basicUser())
        ->patch(action([ClearInjuryController::class], $this->manager))
        ->assertForbidden();
});

test('a guest cannot mark an injured manager as cleared', function () {
    $this->patch(action([ClearInjuryController::class], $this->manager))
        ->assertRedirect(route('login'));
});

test('invoke throws exception for injuring a non injurable manager', function ($factoryState) {
    $this->withoutExceptionHandling();

    $manager = Manager::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([ClearInjuryController::class], $manager));
})->throws(CannotBeClearedFromInjuryException::class)->with([
    'unemployed',
    'available',
    'withFutureEmployment',
    'suspended',
    'retired',
    'released',
]);
