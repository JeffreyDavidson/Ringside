<?php

declare(strict_types=1);

use App\Http\Controllers\TitlesController;
use App\Livewire\Titles\Tables\TitlesTable;
use App\Models\Title;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('index', function () {
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action([TitlesController::class, 'index']))
            ->assertOk()
            ->assertViewIs('titles.index')
            ->assertSeeLivewire(TitlesTable::class);
    });

    test('a basic user cannot view titles index page', function () {
        actingAs(basicUser())
            ->get(action([TitlesController::class, 'index']))
            ->assertForbidden();
    });

    test('a guest cannot view titles index page', function () {
        get(action([TitlesController::class, 'index']))
            ->assertRedirect(route('login'));
    });
});

describe('show', function () {
    beforeEach(function () {
        $this->title = Title::factory()->create();
    });

    test('show returns a view', function () {
        actingAs(administrator())
            ->get(action([TitlesController::class, 'show'], $this->title))
            ->assertOk()
            ->assertViewIs('titles.show')
            ->assertViewHas('title', $this->title);
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
});
