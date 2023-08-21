<?php

use App\Actions\Referees\UnretireAction;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Controllers\Referees\UnretireController;
use App\Models\Referee;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->referee = Referee::factory()->retired()->create();
});

test('invoke calls unretire action and redirects', function () {
    actingAs(administrator())
        ->patch(action([UnretireController::class], $this->referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    UnretireAction::shouldRun()->with($this->referee);
});

test('a basic user cannot unretire a referee', function () {
    actingAs(basicUser())
        ->patch(action([UnretireController::class], $this->referee))
        ->assertForbidden();
});

test('a guest cannot unretire a referee', function () {
    patch(action([UnretireController::class], $this->referee))
        ->assertRedirect(route('login'));
});

test('invoke returns error message if exception is thrown', function () {
    $referee = Referee::factory()->create();

    UnretireAction::allowToRun()->andThrow(CannotBeUnretiredException::class);

    actingAs(administrator())
        ->from(action([RefereesController::class, 'index']))
        ->patch(action([UnretireController::class], $referee))
        ->assertRedirect(action([RefereesController::class, 'index']))
        ->assertSessionHas('error');
});
