<?php

use App\Actions\Referees\RetireAction;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Controllers\Referees\RetireController;
use App\Models\Referee;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->referee = Referee::factory()->bookable()->create();
});

test('invoke calls retire action and redirects', function () {
    actingAs(administrator())
        ->patch(action([RetireController::class], $this->referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    RetireAction::shouldRun()->with($this->referee);
});

test('a basic user cannot retire a referee', function () {
    actingAs(basicUser())
        ->patch(action([RetireController::class], $this->referee))
        ->assertForbidden();
});

test('a guest cannot retire a referee', function () {
    patch(action([RetireController::class], $this->referee))
        ->assertRedirect(route('login'));
});
