<?php

use App\Http\Controllers\Titles\TitlesController;
use App\Models\Title;

test('index returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([TitlesController::class, 'index']))
        ->assertOk()
        ->assertViewIs('titles.index')
        ->assertSeeLivewire('titles.titles-list');
});

test('a basic user cannot view titles index page', function () {
    $this->actingAs(basicUser())
        ->get(action([TitlesController::class, 'index']))
        ->assertForbidden();
});

test('a guest cannot view titles index page', function () {
    $this->get(action([TitlesController::class, 'index']))
        ->assertRedirect(route('login'));
});

test('show returns a view', function () {
    $title = Title::factory()->create();

    $this->actingAs(administrator())
        ->get(action([TitlesController::class, 'show'], $title))
        ->assertOk()
        ->assertViewIs('titles.show')
        ->assertViewHas('title', $title)
        ->assertSeeLivewire('titles.title-championships-list');
});

test('a basic user cannot view a title', function () {
    $title = Title::factory()->create();

    $this->actingAs(basicUser())
        ->get(action([TitlesController::class, 'show'], $title))
        ->assertForbidden();
});

test('a guest cannot view a title', function () {
    $title = Title::factory()->create();

    $this->get(action([TitlesController::class, 'show'], $title))
        ->assertRedirect(route('login'));
});

test('deletes a title and redirects', function () {
    $title = Title::factory()->create();

    $this->actingAs(administrator())
        ->delete(action([TitlesController::class, 'destroy'], $title))
        ->assertRedirect(action([TitlesController::class, 'index']));

    $this->assertSoftDeleted($title);
});

test('a basic user cannot delete a title', function () {
    $title = Title::factory()->create();

    $this->actingAs(basicUser())
        ->delete(action([TitlesController::class, 'destroy'], $title))
        ->assertForbidden();
});

test('a guest cannot delete a title', function () {
    $title = Title::factory()->create();

    $this->delete(action([TitlesController::class, 'destroy'], $title))
        ->assertRedirect(route('login'));
});
