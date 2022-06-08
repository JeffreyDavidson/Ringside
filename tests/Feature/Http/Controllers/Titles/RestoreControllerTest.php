<?php

namespace Tests\Feature\Http\Controllers\Titles;

use App\Http\Controllers\Titles\RestoreController;
use App\Http\Controllers\Titles\TitlesController;
use App\Models\Title;

beforeEach(function () {
    $this->title = Title::factory()->trashed()->create();
});

test('invoke restores a deleted title and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([RestoreController::class], $this->title))
        ->assertRedirect(action([TitlesController::class, 'index']));

    $this->assertNull($this->title->fresh()->deleted_at);
});

test('a basic user cannot restore a title', function () {
    $this->actingAs(basicUser())
        ->patch(action([RestoreController::class], $this->title))
        ->assertForbidden();
});

test('a guest cannot restore a title', function () {
    $this->patch(action([RestoreController::class], $this->title))
        ->assertRedirect(route('login'));
});
