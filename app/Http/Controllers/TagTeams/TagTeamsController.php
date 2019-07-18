<?php

namespace App\Http\Controllers\TagTeams;

use App\Models\TagTeam;
use Illuminate\Http\Request;
use App\Filters\TagTeamFilters;
use Yajra\DataTables\DataTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTagTeamRequest;
use App\Http\Requests\UpdateTagTeamRequest;

class TagTeamsController extends Controller
{
    /**
     * View a list of tag teams.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Yajra\DataTables\DataTables  $table
     * @return \Illuminate\View\View
     */
    public function index(Request $request, DataTables $table, TagTeamFilters $requestFilter)
    {
        $this->authorize('viewList', TagTeam::class);

        if ($request->ajax()) {
            $query = TagTeam::query();
            $requestFilter->apply($query);

            return $table->eloquent($query)
                ->addColumn('action', 'tagteams.partials.action-cell')
                ->editColumn('started_at', function (TagTeam $tagteam) {
                    return $tagteam->employment->started_at->format('Y-m-d H:s');
                })
                ->filterColumn('id', function ($query, $keyword) {
                    $query->where($query->qualifyColumn('id'), $keyword);
                })
                ->toJson();
        }


        return view('tagteams.index');
    }

    /**
     * Show the form for creating a new tag team.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(TagTeam $tagteam)
    {
        $this->authorize('create', TagTeam::class);

        return response()->view('tagteams.create', compact('tagteam'));
    }

    /**
     * Create a new tag team.
     *
     * @param  \App\Http\Requests\StoreTagTeamRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreTagTeamRequest $request)
    {
        $tagteam = TagTeam::create($request->except(['wrestlers', 'started_at']));
        $tagteam->employments()->create($request->only('started_at'));
        $tagteam->addWrestlers($request->input('wrestlers'));

        return redirect()->route('tagteams.index');
    }

    /**
     * Show the profile of a tag team.
     *
     * @param  \App\Models\TagTeam  $tagteam
     * @return \Illuminate\Http\Response
     */
    public function show(TagTeam $tagteam)
    {
        $this->authorize('view', $tagteam);

        return response()->view('tagteams.show', compact('tagteam'));
    }

    /**
     * Show the form for editing a tag team.
     *
     * @param  \App\Models\TagTeam  $tagteam
     * @return \lluminate\Http\Response
     */
    public function edit(TagTeam $tagteam)
    {
        $this->authorize('update', TagTeam::class);

        return response()->view('tagteams.edit', compact('tagteam'));
    }

    /**
     * Update a given tag team.
     *
     * @param  \App\Http\Requests\UpdateTagTeamRequest  $request
     * @param  \App\Models\TagTeam  $tagteam
     * @return \lluminate\Http\RedirectResponse
     */
    public function update(UpdateTagTeamRequest $request, TagTeam $tagteam)
    {
        $tagteam->update($request->except(['wrestlers', 'started_at']));
        // dd($tagteam->wrestlers->modelKeys());
        $tagteam->employment($request->only('started_at'));
        $tagteam->wrestlers()->sync($request->input('wrestlers'));
        // dd($tagteam->wrestlers->modelKeys());

        return redirect()->route('tagteams.index');
    }

    /**
     * Delete a tag team.
     *
     * @param  App\Models\TagTeam  $tagteam
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(TagTeam $tagteam)
    {
        $this->authorize('delete', TagTeam::class);

        $tagteam->delete();

        return redirect()->route('tagteams.index');
    }
}
