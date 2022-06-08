<?php

namespace Tests\Feature\Http\Controllers\Managers;

use App\Http\Controllers\Managers\ManagersController;
use App\Http\Controllers\Managers\RestoreController;
use App\Models\Manager;

test('invoke restores a deleted manager and redirects', function () {
    $manager = Manager::factory()->trashed()->create();

    $this->actingAs(administrator())
        ->patch(action([RestoreController::class], $manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    $this->assertNull($manager->fresh()->deleted_at);
});

test('a basic user cannot restore a deleted manager', function () {
    $manager = Manager::factory()->trashed()->create();

    $this->actingAs(basicUser())
        ->patch(action([RestoreController::class], $manager))
        ->assertForbidden();
});

test('a guest cannot restore a deleted manager', function () {
    $manager = Manager::factory()->trashed()->create();

    $this->patch(action([RestoreController::class], $manager))
        ->assertRedirect(route('login'));
});
