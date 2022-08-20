<?php

declare(strict_types=1);

namespace App\Http\Controllers\TagTeams;

use App\Actions\TagTeams\CreateAction;
use App\Actions\TagTeams\DeleteAction;
use App\Actions\TagTeams\UpdateAction;
use App\Data\TagTeamData;
use App\Http\Controllers\Controller;
use App\Http\Requests\TagTeams\StoreRequest;
use App\Http\Requests\TagTeams\UpdateRequest;
use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;

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
     * @param  TagTeam  $tagTeam
     * @return \Illuminate\View\View
     */
    public function create(TagTeam $tagTeam)
    {
        $this->authorize('create', TagTeam::class);

        return view('tagteams.create', [
            'tagTeam' => $tagTeam,
            'wrestlers' => WrestlerRepository::getAvailableWrestlersForNewTagTeam()->pluck('name', 'id'),
        ]);
    }

    /**
     * Create a new tag team.
     *
     * @param  \App\Http\Requests\TagTeams\StoreRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRequest $request)
    {
        CreateAction::run(TagTeamData::fromStoreRequest($request));

        return to_route('tag-teams.index');
    }

    /**
     * Show the profile of a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @return \Illuminate\View\View
     */
    public function show(TagTeam $tagTeam)
    {
        $this->authorize('view', $tagTeam);

        return view('tagteams.show', [
            'tagTeam' => $tagTeam,
        ]);
    }

    /**
     * Show the form for editing a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @return \Illuminate\View\View
     */
    public function edit(TagTeam $tagTeam)
    {
        $this->authorize('update', $tagTeam);

        $wrestlers = Wrestler::withTrashed()->bookable()->pluck('name', 'id');

        return view('tagteams.edit', [
            'tagTeam' => $tagTeam,
            'wrestlers' => $wrestlers,
        ]);
    }

    /**
     * Update a given tag team.
     *
     * @param  \App\Http\Requests\TagTeams\UpdateRequest  $request
     * @param  \App\Models\TagTeam  $tagTeam
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateRequest $request, TagTeam $tagTeam)
    {
        UpdateAction::run($tagTeam, TagTeamData::fromUpdateRequest($request));

        return to_route('tag-teams.index');
    }

    /**
     * Delete a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(TagTeam $tagTeam)
    {
        $this->authorize('delete', $tagTeam);

        DeleteAction::run($tagTeam);

        return to_route('tag-teams.index');
    }
}
