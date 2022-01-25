<?php

namespace App\Http\Controllers\Stables;

use App\DataTransferObjects\StableData;
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
     * @param Stable $stable
     *
     * @return \Illuminate\View\View
     */
    public function create(Stable $stable)
    {
        $this->authorize('create', Stable::class);

        return view('stables.create', [
            'stable' => $stable,
        ]);
    }

    /**
     * Create a new stable.
     *
     * @param  \App\Http\Requests\Stables\StoreRequest  $request
     * @param  \App\DataTransferObjects\StableData $stableData
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRequest $request, StableData $stableData)
    {
        $this->stableService->create($stableData->fromStoreRequest($request));

        return redirect()->route('stables.index');
    }

    /**
     * Show the profile of a tag team.
     *
     * @param  \App\Models\Stable  $stable
     *
     * @return \Illuminate\View\View
     */
    public function show(Stable $stable)
    {
        $this->authorize('view', $stable);

        return view('stables.show', [
            'stable' => $stable,
        ]);
    }

    /**
     * Show the form for editing a stable.
     *
     * @param  \App\Models\Stable  $stable
     *
     * @return \Illuminate\View\View
     */
    public function edit(Stable $stable)
    {
        $this->authorize('update', $stable);

        return view('stables.edit', [
            'stable' => $stable,
        ]);
    }

    /**
     * Update a given stable.
     *
     * @param  \App\Http\Requests\Stables\UpdateRequest  $request
     * @param  \App\Models\Stable  $stable
     * @param  \App\DataTransferObjects\StableData $stableData
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateRequest $request, Stable $stable, StableData $stableData)
    {
        $this->stableService->update($stable, $stableData->fromUpdateRequest($request));

        return redirect()->route('stables.index');
    }

    /**
     * Delete a stable.
     *
     * @param  \App\Models\Stable  $stable
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Stable $stable)
    {
        $this->authorize('delete', Stable::class);

        $this->stableService->delete($stable);

        return redirect()->route('stables.index');
    }
}
