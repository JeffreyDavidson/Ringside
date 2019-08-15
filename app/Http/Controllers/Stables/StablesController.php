<?php

namespace App\Http\Controllers\Stables;

use App\Models\Stable;
use Illuminate\Http\Request;
use App\Filters\StableFilters;
use Yajra\DataTables\DataTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStableRequest;
use App\Http\Requests\UpdateStableRequest;

class StablesController extends Controller
{
    /**
     * View a list of stables.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Yajra\DataTables\DataTables  $table
     * @return \Illuminate\View\View
     */
    public function index(Request $request, DataTables $table, StableFilters $requestFilter)
    {
        $this->authorize('viewList', Stable::class);

        if ($request->ajax()) {
            $query = Stable::query();
            $requestFilter->apply($query);

            return $table->eloquent($query)
                ->addColumn('action', 'stables.partials.action-cell')
                ->editColumn('started_at', function (Stable $stable) {
                    return $stable->employment->started_at ?? null;
                })
                ->filterColumn('id', function ($query, $keyword) {
                    $query->where($query->qualifyColumn('id'), $keyword);
                })
                ->toJson();
        }

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

        return view('stables.create');
    }

    /**
     * Create a new stable.
     *
     * @param  \App\Http\Requests\StoreStableRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreStableRequest $request)
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
     * @return \lluminate\Http\Response
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
     * @return \lluminate\Http\RedirectResponse
     */
    public function update(UpdateStableRequest $request, Stable $stable)
    {
        $stable->update($request->except('wrestlers', 'tagteams', 'started_at'));

        if ($request->filled('started_at')) {
            if ($stable->employment && $stable->employment->started_at != $request->input('started_at')) {
                $stable->employment()->update($request->only('started_at'));
            } elseif (!$stable->employment) {
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
