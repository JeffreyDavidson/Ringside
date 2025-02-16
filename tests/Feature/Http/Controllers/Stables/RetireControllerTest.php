<?php

declare(strict_types=1);

use App\Actions\Stables\RetireAction;
use App\Http\Controllers\Stables\RetireController;
use App\Http\Controllers\Stables\StablesController;
use App\Models\Stable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->stable = Stable::factory()->active()->create();
});

test('invoke calls retire action and redirects', function () {
    actingAs(administrator())
        ->patch(action([RetireController::class], $this->stable))
        ->assertRedirect(action([StablesController::class, 'index']));

    RetireAction::shouldRun()->with($this->stable);
});

test('a basic user cannot retire a stable', function () {
    actingAs(basicUser())
        ->patch(action([RetireController::class], $this->stable))
        ->assertForbidden();
});

test('a guest cannot retire a stable', function () {
    patch(action([RetireController::class], $this->stable))
        ->assertRedirect(route('login'));
});
