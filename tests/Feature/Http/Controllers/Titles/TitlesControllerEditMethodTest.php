<?php

declare(strict_types=1);

use App\Http\Controllers\Titles\TitlesController;
use App\Models\Title;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->title = Title::factory()->create();
});

test('edit returns a view', function () {
    actingAs(administrator())
        ->get(action([TitlesController::class, 'edit'], $this->title))
        ->assertOk()
        ->assertViewIs('titles.edit')
        ->assertViewHas('title', $this->title);
});

test('a basic user cannot view the form for editing a title', function () {
    actingAs(basicUser())
        ->get(action([TitlesController::class, 'edit'], $this->title))
        ->assertForbidden();
});

test('a guest cannot view the form for editing a title', function () {
get(action([TitlesController::class, 'edit'], $this->title))
->assertRedirect(route('login'));
    });
