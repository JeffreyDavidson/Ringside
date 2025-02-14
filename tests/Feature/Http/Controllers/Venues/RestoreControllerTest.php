<?php

declare(strict_types=1);

use App\Actions\Venues\RestoreAction;
use App\Http\Controllers\Venues\RestoreController;
use App\Http\Controllers\Venues\VenuesController;
use App\Models\Venue;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->venue = Venue::factory()->trashed()->create();
});

test('invoke calls restore action and redirects', function () {
    actingAs(administrator())
        ->patch(action([RestoreController::class], $this->venue))
        ->assertRedirect(action([VenuesController::class, 'index']));

    RestoreAction::shouldRun()->with($this->venue);
});

test('a basic user cannot restore a venue', function () {
    actingAs(basicUser())
        ->patch(action([RestoreController::class], $this->venue))
        ->assertForbidden();
});

test('a guest cannot restore a venue', function () {
    patch(action([RestoreController::class], $this->venue))
        ->assertRedirect(route('login'));
});
