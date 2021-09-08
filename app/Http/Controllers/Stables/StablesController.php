<?php

namespace App\Http\Controllers\Stables;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stables\StoreRequest;
use App\Http\Requests\Stables\UpdateRequest;
use App\Models\Stable;
use App\Services\StableService;

class StablesController extends Controller
{
    public StableService $stableService;

    /**
     * Create a new stables controller instance.
     *
     * @param  \App\Services\StableService $stableService
     */
    public function __construct(StableService $stableService)
    {
        $this->stableService = $stableService;
    }

    /**
     * View a list of stables.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $this->authorize('viewList', Stable::class);

        return view('stables.index');
    }

    /**
     * Show the form for creating a stable.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Stable $stable)
    {
        $this->authorize('create', Stable::class);

        return view('stables.create', compact('stable'));
    }

    /**
     * Create a new stable.
     *
     * @param  \App\Http\Requests\Stables\StoreRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRequest $request)
    {
        $this->stableService->create($request->only(['name', 'started_at', 'wrestlers', 'tag_teams']));

        return redirect()->route('stables.index');
    }

    /**
     * Show the profile of a tag team.
     *
     * @param  \App\Models\Stable  $stable
     * @return \Illuminate\Http\Response
     */
    public function show(Stable $stable)
    {
        $this->authorize('view', $stable);

        return view('stables.show', compact('stable'));
    }

    /**
     * Show the form for editing a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @return \Illuminate\Http\Response
     */
    public function edit(Stable $stable)
    {
        $this->authorize('update', $stable);

        return view('stables.edit', compact('stable'));
    }

    /**
     * Update a given stable.
     *
     * @param  \App\Http\Requests\UpdateStableRequest  $request
     * @param  \App\Models\Stable  $stable
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateRequest $request, Stable $stable)
    {
        $this->stableService->update($stable, $request->only(['name', 'started_at', 'wrestlers', 'tag_teams']));

        return redirect()->route('stables.index');
    }

    /**
     * Delete a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Stable $stable)
    {
        $this->authorize('delete', Stable::class);

        $this->stableService->delete($stable);

        return redirect()->route('stables.index');
    }
}
