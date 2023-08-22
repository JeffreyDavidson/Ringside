<?php

declare(strict_types=1);

use App\Actions\Referees\SuspendAction;
use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Controllers\Referees\SuspendController;
use App\Models\Referee;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->referee = Referee::factory()->bookable()->create();
});

test('invoke calls suspend action and redirects', function () {
    actingAs(administrator())
        ->patch(action([SuspendController::class], $this->referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    SuspendAction::shouldRun()->with($this->referee);
});

test('a basic user cannot suspend a referee', function () {
    actingAs(basicUser())
        ->patch(action([SuspendController::class], $this->referee))
        ->assertForbidden();
});

test('a guest cannot suspend a referee', function () {
    patch(action([SuspendController::class], $this->referee))
        ->assertRedirect(route('login'));
});

test('invoke returns error message if exception is thrown', function () {
    $referee = Referee::factory()->create();

    SuspendAction::allowToRun()->andThrow(CannotBeSuspendedException::class);

    actingAs(administrator())
        ->from(action([RefereesController::class, 'index']))
        ->patch(action([SuspendController::class], $referee))
        ->assertRedirect(action([RefereesController::class, 'index']))
        ->assertSessionHas('error');
});
