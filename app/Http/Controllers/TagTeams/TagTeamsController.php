<?php

namespace App\Http\Controllers\TagTeams;

use App\Http\Controllers\Controller;
use App\Http\Requests\TagTeams\StoreRequest;
use App\Http\Requests\TagTeams\UpdateRequest;
use App\Models\TagTeam;
use App\ViewModels\TagTeamViewModel;

class TagTeamsController extends Controller
{
    /**
     * View a list of tag teams.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $this->authorize('viewList', TagTeam::class);

        return view('tagteams.index');
    }

    /**
     * Show the form for creating a new tag team.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', TagTeam::class);

        return view('tagteams.create', new TagTeamViewModel());
    }

    /**
     * Create a new tag team.
     *
     * @param  App\Http\Requests\TagTeams\StoreRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRequest $request)
    {
        $tagTeam = TagTeam::create($request->except(['wrestler1', 'wrestler2', 'started_at']));

        if ($request->filled('started_at')) {
            $tagTeam->employ($request->input('started_at'));

            $tagTeam->addWrestlers(
                [$request->input('wrestler1'), $request->input('wrestler2')],
                $request->input('started_at')
            );

        } else {
            $tagTeam->addWrestlers(
                [$request->input('wrestler1'), $request->input('wrestler2')]
            );
        }

        return redirect()->route('tag-teams.index');
    }

    /**
     * Show the profile of a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @return \Illuminate\Http\Response
     */
    public function show(TagTeam $tagTeam)
    {
        $this->authorize('view', $tagTeam);

        return view('tagteams.show', compact('tagTeam'));
    }

    /**
     * Show the form for editing a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @return \lluminate\Http\Response
     */
    public function edit(TagTeam $tagTeam)
    {
        $this->authorize('update', $tagTeam);

        return view('tagteams.edit', new TagTeamViewModel($tagTeam));
    }

    /**
     * Update a given tag team.
     *
     * @param  App\Http\Requests\TagTeams\UpdateRequest  $request
     * @param  App\Models\TagTeam  $tagTeam
     * @return \lluminate\Http\RedirectResponse
     */
    public function update(UpdateRequest $request, TagTeam $tagTeam)
    {
        $tagTeam->update($request->except(['wrestler1', 'wrestler2', 'started_at']));

        $tagTeam->employ($request->input('started_at'));

        $tagTeam->wrestlerHistory()->sync(
            [$request->input('wrestler1'), $request->input('wrestler2')],
            ['joined_at' => $request->input('started_at')]
        );

        return redirect()->route('tag-teams.index');
    }

    /**
     * Delete a tag team.
     *
     * @param  App\Models\TagTeam  $tagTeam
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(TagTeam $tagTeam)
    {
        $this->authorize('delete', $tagTeam);

        $tagTeam->delete();

        return redirect()->route('tag-teams.index');
    }
}
