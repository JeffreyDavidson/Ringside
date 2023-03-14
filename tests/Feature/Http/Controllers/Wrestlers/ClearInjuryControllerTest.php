<?php

use App\Actions\Wrestlers\ClearInjuryAction;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Http\Controllers\Wrestlers\ClearInjuryController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\Wrestler;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->wrestler = Wrestler::factory()->injured()->create();
});

test('invoke calls clear injury action and redirects', function () {
    actingAs(administrator())
        ->patch(action([ClearInjuryController::class], $this->wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    ClearInjuryAction::shouldRun()->with($this->wrestler);
});

test('a basic user cannot mark an injured wrestler as cleared', function () {
    actingAs(basicUser())
        ->patch(action([ClearInjuryController::class], $this->wrestler))
        ->assertForbidden();
});

test('a guest cannot mark an injured wrestler as cleared', function () {
    patch(action([ClearInjuryController::class], $this->wrestler))
        ->assertRedirect(route('login'));
});

test('invoke returns error message if exception is thrown', function () {
    $wrestler = Wrestler::factory()->create();

    ClearInjuryAction::allowToRun()->andThrow(CannotBeClearedFromInjuryException::class);

    actingAs(administrator())
        ->from(action([WrestlersController::class, 'index']))
        ->patch(action([ClearInjuryController::class], $wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']))
        ->assertSessionHas('error');
});
