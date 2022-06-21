<?php

use App\Actions\Wrestlers\ClearInjuryAction;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Http\Controllers\Wrestlers\ClearInjuryController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\Employment;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->wrestler = Wrestler::factory()->injured()->create(['name' => 'Injured Wrestler']);
});

test('invoke marks an injured wrestler as being cleared from injury and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([ClearInjuryController::class], $this->wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    ClearInjuryAction::allowToRun()->with($this->wrestler)->andReturn();
});

test('clearing an injured wrestler on an unbookable tag team makes tag team bookable', function () {
    $bookableWrestler = Wrestler::factory()->bookable()->create();
    $tagTeam = TagTeam::factory()
        ->hasAttached($this->wrestler, ['joined_at' => Carbon::yesterday()->toDateTimeString()])
        ->hasAttached($bookableWrestler, ['joined_at' => Carbon::yesterday()->toDateTimeString()])
        ->has(Employment::factory()->started(Carbon::yesterday()))
        ->create();

    $this->actingAs(administrator())
        ->patch(action([ClearInjuryController::class], $this->wrestler));

    expect($this->wrestler->fresh())
        ->status->toMatchObject(WrestlerStatus::BOOKABLE);

    expect($tagTeam->fresh())
        ->status->toMatchObject(TagTeamStatus::BOOKABLE);
});

test('a basic user cannot mark an injured wrestler as cleared', function () {
    $this->actingAs(basicUser())
        ->patch(action([ClearInjuryController::class], $this->wrestler))
        ->assertForbidden();
});

test('a guest cannot mark an injured wrestler as cleared', function () {
    $this->patch(action([ClearInjuryController::class], $this->wrestler))
        ->assertRedirect(route('login'));
});

test('invoke throws exception for injuring a non injurable wrestler', function ($factoryState) {
    $this->withoutExceptionHandling();

    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([ClearInjuryController::class], $wrestler));
})->throws(CannotBeClearedFromInjuryException::class)->with([
    'unemployed',
    'released',
    'withFutureEmployment',
    'bookable',
    'retired',
    'suspended',
]);
