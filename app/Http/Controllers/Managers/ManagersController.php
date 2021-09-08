<?php

namespace App\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\StoreRequest;
use App\Http\Requests\Managers\UpdateRequest;
use App\Models\Manager;
use App\Services\ManagerService;

class ManagersController extends Controller
{
    public ManagerService $managerService;

    /**
     * Create a new managers controller instance.
     *
     * @param \App\Services\ManagerService $managerService
     */
    public function __construct(ManagerService $managerService)
    {
        $this->managerService = $managerService;
    }

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
     * @param  \App\Models\Manager $manager
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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRequest $request)
    {
        $this->managerService->create($request->validated());

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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateRequest $request, Manager $manager)
    {
        $this->managerService->update($manager, $request->validated());

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

        $this->managerService->delete($manager);

        return redirect()->route('managers.index');
    }
}
