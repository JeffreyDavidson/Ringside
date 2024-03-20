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
use App\Models\Manager;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class StablesController extends Controller
{
    /**
     * View a list of stables.
     */
    public function index(): View
    {
        Gate::authorize('viewList', Stable::class);

        return view('stables.index');
    }

    /**
     * Show the form for creating a stable.
     */
    public function create(Stable $stable): View
    {
        Gate::authorize('create', Stable::class);

        return view('stables.create', [
            'stable' => $stable,
            'wrestlers' => Wrestler::query()->pluck('name', 'id'),
            'tagTeams' => TagTeam::query()->pluck('name', 'id'),
            'managers' => Manager::query()->get()->pluck('full_name', 'id'),
        ]);
    }

    /**
     * Create a new stable.
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        CreateAction::run(StableData::fromStoreRequest($request));

        return to_route('stables.index');
    }

    /**
     * Show the profile of a tag team.
     */
    public function show(Stable $stable): View
    {
        Gate::authorize('view', $stable);

        return view('stables.show', [
            'stable' => $stable,
        ]);
    }

    /**
     * Show the form for editing a stable.
     */
    public function edit(Stable $stable): View
    {
        Gate::authorize('update', $stable);

        return view('stables.edit', [
            'stable' => $stable,
            'wrestlers' => Wrestler::query()->pluck('name', 'id'),
            'tagTeams' => TagTeam::query()->pluck('name', 'id'),
            'managers' => Manager::query()->get()->pluck('full_name', 'id'),
        ]);
    }

    /**
     * Update a given stable.
     */
    public function update(UpdateRequest $request, Stable $stable): RedirectResponse
    {
        UpdateAction::run($stable, StableData::fromUpdateRequest($request));

        return to_route('stables.index');
    }

    /**
     * Delete a stable.
     */
    public function destroy(Stable $stable): RedirectResponse
    {
        Gate::authorize('delete', Stable::class);

        DeleteAction::run($stable);

        return to_route('stables.index');
    }
}
