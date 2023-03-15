<?php

use App\Actions\Referees\InjureAction;
use App\Exceptions\CannotBeInjuredException;
use App\Http\Controllers\Referees\InjureController;
use App\Http\Controllers\Referees\RefereesController;
use App\Models\Referee;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->referee = Referee::factory()->bookable()->create();
});

test('invoke calls injure action and redirects', function () {
    actingAs(administrator())
        ->patch(action([InjureController::class], $this->referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    InjureAction::shouldRun()->with($this->referee);
});

test('a basic user cannot injure a referee', function () {
    actingAs(basicUser())
        ->patch(action([InjureController::class], $this->referee))
        ->assertForbidden();
});

test('a guest user cannot injure a referee', function () {
    patch(action([InjureController::class], $this->referee))
        ->assertRedirect(route('login'));
});

test('invoke returns error message if exception is thrown', function () {
    $referee = Referee::factory()->create();

    InjureAction::allowToRun()->andThrow(CannotBeInjuredException::class);

    actingAs(administrator())
        ->from(action([RefereesController::class, 'index']))
        ->patch(action([InjureController::class], $referee))
        ->assertRedirect(action([RefereesController::class, 'index']))
        ->assertSessionHas('error');
});
