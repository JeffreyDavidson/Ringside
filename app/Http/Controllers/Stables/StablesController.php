<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stables;

use App\Actions\Stables\CreateAction;
use App\Actions\Stables\DeleteAction;
use App\Actions\Stables\UpdateAction;
use App\Data\StableData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stables\StoreRequest;
use App\Http\Requests\Stables\UpdateRequest;
use App\Models\Stable;

class StablesController extends Controller
{
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
     * @param  Stable  $stable
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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRequest $request)
    {
        CreateAction::run(StableData::fromStoreRequest($request));

        return to_route('stables.index');
    }

    /**
     * Show the profile of a tag team.
     *
     * @param  \App\Models\Stable  $stable
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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateRequest $request, Stable $stable)
    {
        UpdateAction::run($stable, StableData::fromUpdateRequest($request));

        return to_route('stables.index');
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

        DeleteAction::run($stable);

        return to_route('stables.index');
    }
}
