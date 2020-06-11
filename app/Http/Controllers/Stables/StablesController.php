<?php

namespace App\Http\Controllers\Stables;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stables\StoreRequest;
use App\Http\Requests\Stables\UpdateRequest;
use App\Models\Stable;
use App\ViewModels\StableViewModel;

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
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', Stable::class);

        return view('stables.create', new StableViewModel());
    }

    /**
     * Create a new stable.
     *
     * @param  App\Http\Requests\StoreStableRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRequest $request)
    {
        $stable = Stable::create($request->except(['wrestlers', 'tagteams', 'started_at']));

        if ($request->filled('started_at')) {
            $stable->employments()->create($request->only('started_at'));
        }

        if ($request->filled('wrestlers')) {
            $stable->addWrestlers($request->input('wrestlers'), $request->input('started_at'));
        }

        if ($request->filled('tagteams')) {
            $stable->addTagTeams($request->input('tagteams'), $request->input('started_at'));
        }

        return redirect()->route('stables.index');
    }

    /**
     * Show the profile of a tag team.
     *
     * @param  App\Models\Stable  $stable
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
     * @param  App\Models\Stable  $stable
     * @return \lluminate\Http\Response
     */
    public function edit(Stable $stable)
    {
        $this->authorize('update', $stable);

        return view('stables.edit', new StableViewModel($stable));
    }

    /**
     * Update a given stable.
     *
     * @param  App\Http\Requests\UpdateStableRequest  $request
     * @param  App\Models\Stable  $stable
     * @return \lluminate\Http\RedirectResponse
     */
    public function update(UpdateRequest $request, Stable $stable)
    {
        $stable->update($request->except('wrestlers', 'tagteams', 'started_at'));

        if ($request->filled('started_at')) {
            if ($stable->currentEmployment && $stable->currentEmployment->started_at != $request->input('started_at')) {
                $stable->currentEmployment()->update($request->only('started_at'));
            } elseif (!$stable->currentEmployment) {
                $stable->employments()->create($request->only('started_at'));
            }
        }

        $stable->wrestlerHistory()->sync($request->input('wrestlers'));
        $stable->tagTeamHistory()->sync($request->input('tagteams'));

        return redirect()->route('stables.index');
    }

    /**
     * Delete a stable.
     *
     * @param  App\Models\Stable  $stable
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(Stable $stable)
    {
        $this->authorize('delete', Stable::class);

        $stable->delete();

        return redirect()->route('stables.index');
    }
}
