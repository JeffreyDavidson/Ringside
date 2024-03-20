<?php

declare(strict_types=1);

namespace App\Http\Controllers\Managers;

use App\Actions\Managers\CreateAction;
use App\Actions\Managers\DeleteAction;
use App\Actions\Managers\UpdateAction;
use App\Data\ManagerData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\StoreRequest;
use App\Http\Requests\Managers\UpdateRequest;
use App\Models\Manager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ManagersController extends Controller
{
    /**
     * View a list of managers.
     */
    public function index(): View
    {
        Gate::authorize('viewList', Manager::class);

        return view('managers.index');
    }

    /**
     * Show the form for creating a manager.
     */
    public function create(Manager $manager): View
    {
        Gate::authorize('create', Manager::class);

        return view('managers.create', [
            'manager' => $manager,
        ]);
    }

    /**
     * Create a new manager.
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        CreateAction::run(ManagerData::fromStoreRequest($request));

        return to_route('managers.index');
    }

    /**
     * Show the profile of a manager.
     */
    public function show(Manager $manager): View
    {
        Gate::authorize('view', $manager);

        return view('managers.show', [
            'manager' => $manager,
        ]);
    }

    /**
     * Show the form for editing a manager.
     */
    public function edit(Manager $manager): View
    {
        Gate::authorize('update', $manager);

        return view('managers.edit', [
            'manager' => $manager,
        ]);
    }

    /**
     * Update a given manager.
     */
    public function update(UpdateRequest $request, Manager $manager): RedirectResponse
    {
        UpdateAction::run($manager, ManagerData::fromUpdateRequest($request));

        return to_route('managers.index');
    }

    /**
     * Delete a manager.
     */
    public function destroy(Manager $manager): RedirectResponse
    {
        Gate::authorize('delete', $manager);

        DeleteAction::run($manager);

        return to_route('managers.index');
    }
}
