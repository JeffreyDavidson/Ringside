<?php

use App\Http\Controllers\Titles\TitlesController;
use App\Models\Title;

beforeEach(function () {
    $this->title = Title::factory()->create();
});

test('edit returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([TitlesController::class, 'edit'], $this->title))
        ->assertStatus(200)
        ->assertViewIs('titles.edit')
        ->assertViewHas('title', $this->title);
});

test('a basic user cannot view the form for editing a title', function () {
    $this->actingAs(basicUser())
        ->get(action([TitlesController::class, 'edit'], $this->title))
        ->assertForbidden();
});

test('a guest cannot view the form for editing a title', function () {
    $this->get(action([TitlesController::class, 'edit'], $this->title))
        ->assertRedirect(route('login'));
});
