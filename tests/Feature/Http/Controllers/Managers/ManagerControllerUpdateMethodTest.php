<?php

declare(strict_types=1);

use App\Actions\Managers\UpdateAction;
use App\Data\ManagerData;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Requests\Managers\UpdateRequest;
use App\Models\Manager;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->manager = Manager::factory()->create();
    $this->data = UpdateRequest::factory()->create();
    $this->request = UpdateRequest::create(
        action([ManagersController::class, 'update'], $this->manager),
        'PATCH',
        $this->data
    );
});

test('update calls update action and redirects', function () {
    actingAs(administrator())
        ->from(action([ManagersController::class, 'edit'], $this->manager))
        ->patch(action([ManagersController::class, 'update'], $this->manager), $this->data)
        ->assertValid()
        ->assertRedirect(action([ManagersController::class, 'index']));

    UpdateAction::shouldRun()->with($this->manager, ManagerData::fromUpdateRequest($this->request));
});

test('a basic user cannot update a manager', function () {
    actingAs(basicUser())
        ->patch(action([ManagersController::class, 'update'], $this->manager), $this->data)
        ->assertForbidden();
});

test('a guest cannot update a manager', function () {
    patch(action([ManagersController::class, 'update'], $this->manager), $this->data)
        ->assertRedirect(route('login'));
});
