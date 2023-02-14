<?php

declare(strict_types=1);

namespace App\Http\Controllers\Managers;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Actions\Managers\CreateAction;
use App\Actions\Managers\DeleteAction;
use App\Actions\Managers\UpdateAction;
use App\Data\ManagerData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\StoreRequest;
use App\Http\Requests\Managers\UpdateRequest;
use App\Models\Manager;

class ManagersController extends Controller
{
    /**
     * View a list of managers.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        $this->authorize('viewList', Manager::class);

        return view('managers.index');
    }

    /**
     * Show the form for creating a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @return \Illuminate\View\View
     */
    public function create(Manager $manager): View
    {
        $this->authorize('create', Manager::class);

        return view('managers.create', [
            'manager' => $manager,
        ]);
    }

    /**
     * Create a new manager.
     *
     * @param  \App\Http\Requests\Managers\StoreRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        CreateAction::run(ManagerData::fromStoreRequest($request));

        return to_route('managers.index');
    }

    /**
     * Show the profile of a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @return \Illuminate\View\View
     */
    public function show(Manager $manager): View
    {
        $this->authorize('view', $manager);

        return view('managers.show', [
            'manager' => $manager,
        ]);
    }

    /**
     * Show the form for editing a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @return \Illuminate\View\View
     */
    public function edit(Manager $manager): View
    {
        $this->authorize('update', $manager);

        return view('managers.edit', [
            'manager' => $manager,
        ]);
    }

    /**
     * Update a given manager.
     *
     * @param  \App\Http\Requests\Managers\UpdateRequest  $request
     * @param  \App\Models\Manager  $manager
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateRequest $request, Manager $manager): RedirectResponse
    {
        UpdateAction::run($manager, ManagerData::fromUpdateRequest($request));

        return to_route('managers.index');
    }

    /**
     * Delete a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Manager $manager): RedirectResponse
    {
        $this->authorize('delete', $manager);

        DeleteAction::run($manager);

        return to_route('managers.index');
    }
}
