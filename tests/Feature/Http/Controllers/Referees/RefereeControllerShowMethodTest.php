<?php

declare(strict_types=1);

use App\Http\Controllers\Referees\RefereesController;
use App\Models\Referee;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->referee = Referee::factory()->create();
});

test('show returns a view', function () {
    actingAs(administrator())
        ->get(action([RefereesController::class, 'show'], $this->referee))
        ->assertViewIs('referees.show')
        ->assertViewHas('referee', $this->referee);
});

test('a basic user cannot view a referee profile', function () {
    actingAs(basicUser())
        ->get(action([RefereesController::class, 'show'], $this->referee))
        ->assertForbidden();
});

test('a guest cannot view a referee profile', function () {
get(action([RefereesController::class, 'show'], $this->referee))
->assertRedirect(route('login'));
    });
