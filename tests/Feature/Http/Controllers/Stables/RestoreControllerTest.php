<?php

declare(strict_types=1);

use App\Actions\Stables\RestoreAction;
use App\Http\Controllers\Stables\RestoreController;
use App\Http\Controllers\Stables\StablesController;
use App\Models\Stable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->stable = Stable::factory()->trashed()->create();
});

test('invoke calls restore action and redirects', function () {
    actingAs(administrator())
        ->patch(action([RestoreController::class], $this->stable))
        ->assertRedirect(action([StablesController::class, 'index']));

    RestoreAction::shouldRun()->with($this->stable);
});

test('a basic user cannot restore a stable', function () {
    actingAs(basicUser())
        ->patch(action([RestoreController::class], $this->stable))
        ->assertForbidden();
});

test('a guest cannot restore a stable', function () {
    patch(action([RestoreController::class], $this->stable))
        ->assertRedirect(route('login'));
});
