<?php

declare(strict_types=1);

use App\Http\Controllers\Titles\TitlesController;
use App\Livewire\Titles\TitleChampionshipsList;
use App\Models\Title;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->title = Title::factory()->create();
});

test('show returns a view', function () {
    actingAs(administrator())
        ->get(action([TitlesController::class, 'show'], $this->title))
        ->assertOk()
        ->assertViewIs('titles.show')
        ->assertViewHas('title', $this->title)
        ->assertSeeLivewire(TitleChampionshipsList::class);
});

test('a basic user cannot view a title', function () {
    actingAs(basicUser())
        ->get(action([TitlesController::class, 'show'], $this->title))
        ->assertForbidden();
});

test('a guest cannot view a title', function () {
get(action([TitlesController::class, 'show'], $this->title))
->assertRedirect(route('login'));
    });
