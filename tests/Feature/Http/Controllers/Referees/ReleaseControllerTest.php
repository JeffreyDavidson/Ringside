<?php

declare(strict_types=1);

use App\Actions\Referees\ReleaseAction;
use App\Exceptions\CannotBeReleasedException;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Controllers\Referees\ReleaseController;
use App\Models\Referee;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->referee = Referee::factory()->bookable()->create();
});

test('invoke calls release action and redirects', function () {
    actingAs(administrator())
        ->patch(action([ReleaseController::class], $this->referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    ReleaseAction::shouldRun()->with($this->referee);
});

test('a basic user cannot release a referee', function () {
    actingAs(basicUser())
        ->patch(action([ReleaseController::class], $this->referee))
        ->assertForbidden();
});

test('a guest cannot release a referee', function () {
    patch(action([ReleaseController::class], $this->referee))
        ->assertRedirect(route('login'));
});

test('invoke returns error message if exception is thrown', function () {
    $referee = Referee::factory()->create();

    ReleaseAction::allowToRun()->andThrow(CannotBeReleasedException::class);

    actingAs(administrator())
        ->from(action([RefereesController::class, 'index']))
        ->patch(action([ReleaseController::class], $referee))
        ->assertRedirect(action([RefereesController::class, 'index']))
        ->assertSessionHas('error');
});
