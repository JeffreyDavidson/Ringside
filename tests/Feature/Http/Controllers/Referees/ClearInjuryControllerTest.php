<?php

use App\Actions\Referees\ClearInjuryAction;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Http\Controllers\Referees\ClearInjuryController;
use App\Http\Controllers\Referees\RefereesController;
use App\Models\Referee;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->referee = Referee::factory()->injured()->create();
});

test('invoke calls clear injury action and redirects', function () {
    actingAs(administrator())
        ->patch(action([ClearInjuryController::class], $this->referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    ClearInjuryAction::shouldRun()->with($this->referee);
});

test('a basic user cannot mark an injured referee as cleared', function () {
    actingAs(basicUser())
        ->patch(action([ClearInjuryController::class], $this->referee))
        ->assertForbidden();
});

test('a guest cannot mark an injured referee as cleared', function () {
    patch(action([ClearInjuryController::class], $this->referee))
        ->assertRedirect(route('login'));
});

test('invoke returns error message if exception is thrown', function () {
    $referee = Referee::factory()->create();

    ClearInjuryAction::allowToRun()->andThrow(CannotBeClearedFromInjuryException::class);

    actingAs(administrator())
        ->from(action([RefereesController::class, 'index']))
        ->patch(action([ClearInjuryController::class], $referee))
        ->assertRedirect(action([RefereesController::class, 'index']))
        ->assertSessionHas('error');
});
