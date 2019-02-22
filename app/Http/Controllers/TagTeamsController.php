<?php

namespace App\Http\Controllers;

use App\TagTeam;
use App\Http\Requests\StoreTagTeamRequest;
use App\Http\Requests\UpdateTagTeamRequest;

class TagTeamsController extends Controller
{
    /**
     * Retrieve tag teams of a specific state.
     *
     * @param  string  $state
     * @return \Illuminate\Http\Response
     */
    public function index($state = 'active')
    {
        $this->authorize('viewList', TagTeam::class);

        $tagteams = TagTeam::hasState($state)->get();

        return response()->view('tagteams.index', compact('tagteams'));
    }

    /**
     * Show the form for creating a new tag team.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', TagTeam::class);

        return response()->view('tagteams.create');
    }

    /**
     * Create a new tag team.
     *
     * @param  \App\Http\Requests\StoreTagTeamRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreTagTeamRequest $request)
    {
        $tagteam = TagTeam::create($request->except('wrestlers'));

        $tagteam->addWrestlers($request->input('wrestlers'));

        return redirect()->route('tagteams.index');
    }

    /**
     * Show the profile of a tag team.
     *
     * @param  \App\TagTeam  $tagteam
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
     * @param  \App\TagTeam  $tagteam
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
     * @param  \App\TagTeam  $tagteam
     * @return \lluminate\Http\RedirectResponse
     */
    public function update(UpdateTagTeamRequest $request, TagTeam $tagteam)
    {
        $tagteam->update($request->except('wrestlers'));

        $tagteam->wrestlers()->sync($request->input('wrestlers'));

        return redirect()->route('tagteams.index');
    }

    /**
     * Delete a tag team.
     *
     * @param  App\TagTeam  $tagteam
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(TagTeam $tagteam)
    {
        $this->authorize('delete', TagTeam::class);

        $tagteam->delete();

        return redirect()->route('tagteams.index');
    }

    /**
     * Restore a deleted tag team.
     *
     * @param  int  $tagteamId
     * @return \lluminate\Http\RedirectResponse
     */
    public function restore($tagteamId)
    {
        $tagteam = TagTeam::onlyTrashed()->findOrFail($tagteamId);

        $this->authorize('restore', TagTeam::class);

        $tagteam->restore();

        return redirect()->route('tagteams.index');
    }
}
