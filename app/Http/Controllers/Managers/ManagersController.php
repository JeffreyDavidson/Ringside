<?php

namespace App\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\StoreRequest;
use App\Http\Requests\Managers\UpdateRequest;
use App\Models\Manager;
use App\Services\ManagerService;

class ManagersController extends Controller
{
    /**
     * View a list of managers.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $this->authorize('viewList', Manager::class);

        return view('managers.index');
    }

    /**
     * Show the form for creating a manager.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Manager $manager)
    {
        $this->authorize('create', Manager::class);

        return view('managers.create', compact('manager'));
    }

    /**
     * Create a new manager.
     *
     * @param  \App\Http\Requests\Managers\StoreRequest  $request
     * @param  \App\Services\ManagerService  $managerService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRequest $request, ManagerService $managerService)
    {
        $managerService->create($request->validated());

        return redirect()->route('managers.index');
    }

    /**
     * Show the profile of a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @return \Illuminate\Http\Response
     */
    public function show(Manager $manager)
    {
        $this->authorize('view', $manager);

        return view('managers.show', compact('manager'));
    }

    /**
     * Show the form for editing a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @return \Illuminate\Http\Response
     */
    public function edit(Manager $manager)
    {
        $this->authorize('update', $manager);

        return view('managers.edit', compact('manager'));
    }

    /**
     * Update a given manager.
     *
     * @param  \App\Http\Requests\Managers\UpdateRequest  $request
     * @param  \App\Models\Manager  $manager
     * @param  \App\Services\ManagerService  $managerService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateRequest $request, Manager $manager, ManagerService $managerService)
    {
        $managerService->update($manager, $request->validated());

        return redirect()->route('managers.index');
    }

    /**
     * Delete a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Manager $manager)
    {
        $this->authorize('delete', $manager);

        $manager->delete();

        return redirect()->route('managers.index');
    }
}
