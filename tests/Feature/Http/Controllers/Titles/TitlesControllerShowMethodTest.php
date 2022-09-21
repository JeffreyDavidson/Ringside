<?php

use App\Http\Controllers\Titles\TitlesController;
use App\Models\Title;

beforeEach(function () {
    $this->title = Title::factory()->create();
});

test('show returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([TitlesController::class, 'show'], $this->title))
        ->assertOk()
        ->assertViewIs('titles.show')
        ->assertViewHas('title', $this->title)
        ->assertSeeLivewire('titles.title-championships-list');
});

test('a basic user cannot view a title', function () {
    $this->actingAs(basicUser())
        ->get(action([TitlesController::class, 'show'], $this->title))
        ->assertForbidden();
});

test('a guest cannot view a title', function () {
    $this->get(action([TitlesController::class, 'show'], $this->title))
        ->assertRedirect(route('login'));
});
