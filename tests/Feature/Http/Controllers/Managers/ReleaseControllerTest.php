<?php

use App\Enums\ManagerStatus;
use App\Exceptions\CannotBeReleasedException;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Controllers\Managers\ReleaseController;
use App\Models\Manager;
use App\Models\TagTeam;
use App\Models\Wrestler;

test('invoke releases a available manager and redirects', function () {
    $manager = Manager::factory()->available()->create();

    $this->actingAs(administrator())
        ->patch(action([ReleaseController::class], $manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    expect($manager->fresh())
        ->employments->last()->ended_at->not->toBeNull()
        ->status->toBe(ManagerStatus::RELEASED);
});

test('invoke releases an injured manager and redirects', function () {
    $manager = Manager::factory()->injured()->create();

    $this->actingAs(administrator())
        ->patch(action([ReleaseController::class], $manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    expect($manager->fresh())
        ->injuries->last()->ended_at->not->toBeNull()
        ->employments->last()->ended_at->not->toBeNull()
        ->status->toBe(ManagerStatus::RELEASED);
});

test('invoke releases an suspended manager and redirects', function () {
    $manager = Manager::factory()->suspended()->create();

    $this->actingAs(administrator())
        ->patch(action([ReleaseController::class], $manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    expect($manager->fresh())
        ->suspensions->last()->ended_at->not->toBeNull()
        ->employments->last()->ended_at->not->toBeNull()
        ->status->toBe(ManagerStatus::RELEASED);
});

test('invoke_releases_a_manager_leaving_their_current_tag_teams_and_managers_and_redirects', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();
    $wrestler = Wrestler::factory()->bookable()->create();
    $manager = Manager::factory()
        ->available()
        ->hasAttached($tagTeam, ['hired_at' => now()->toDateTimeString()])
        ->hasAttached($wrestler, ['hired_at' => now()->toDateTimeString()])
        ->create();

    $this->actingAs(administrator())
        ->patch(action([ReleaseController::class], $manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    expect($manager->fresh())
        ->tagTeams()->where('manageable_id', $tagTeam->id)->get()->last()->pivot->left_at->not->toBeNull()
        ->wrestlers()->where('manageable_id', $wrestler->id)->get()->last()->pivot->left_at->not->toBeNull();
});

test('a basic user cannot release a available manager', function () {
    $manager = Manager::factory()->available()->create();

    $this->actingAs(basicUser())
        ->patch(action([ReleaseController::class], $manager))
        ->assertForbidden();
});

test('a guest cannot release a available manager', function () {
    $manager = Manager::factory()->available()->create();

    $this->patch(action([ReleaseController::class], $manager))
        ->assertRedirect(route('login'));
});

test('invoke throws an exception for releasing a non releasable manager', function ($factoryState) {
    $this->withoutExceptionHandling();

    $manager = Manager::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([ReleaseController::class], $manager));
})->throws(CannotBeReleasedException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'released',
    'retired',
]);
