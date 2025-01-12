<?php

declare(strict_types=1);

use App\Http\Controllers\Titles\TitlesController;
use App\Models\Title;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('create returns a view', function () {
    actingAs(administrator())
        ->get(action([TitlesController::class, 'create']))
        ->assertOk()
        ->assertViewIs('titles.create')
        ->assertViewHas('title', new Title);
});

test('a basic user cannot view the form for creating a title', function () {
    actingAs(basicUser())
        ->get(action([TitlesController::class, 'create']))
        ->assertForbidden();
});

test('a guest cannot view the form for creating a title', function () {
get(action([TitlesController::class, 'create']))
->assertRedirect(route('login'));
    });
