<?php

use App\Actions\Wrestlers\InjureAction;
use App\Exceptions\CannotBeInjuredException;
use App\Http\Controllers\Wrestlers\InjureController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\Wrestler;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->wrestler = Wrestler::factory()->bookable()->create();
});

test('invoke calls injure action and redirects', function () {
    actingAs(administrator())
        ->patch(action([InjureController::class], $this->wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    InjureAction::shouldRun()->with($this->wrestler);
});

test('a basic user cannot injure a wrestler', function () {
    actingAs(basicUser())
        ->patch(action([InjureController::class], $this->wrestler))
        ->assertForbidden();
});

test('a guest user cannot injure a wrestler', function () {
    patch(action([InjureController::class], $this->wrestler))
        ->assertRedirect(route('login'));
});

test('invoke returns error message if exception is thrown', function () {
    $wrestler = Wrestler::factory()->create();

    InjureAction::allowToRun()->andThrow(CannotBeInjuredException::class);

    actingAs(administrator())
        ->from(action([WrestlersController::class, 'index']))
        ->patch(action([InjureController::class], $wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']))
        ->assertSessionHas('error');
});
