<?php

declare(strict_types=1);

namespace App\Http\Controllers\TagTeams;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Actions\TagTeams\CreateAction;
use App\Actions\TagTeams\DeleteAction;
use App\Actions\TagTeams\UpdateAction;
use App\Data\TagTeamData;
use App\Http\Controllers\Controller;
use App\Http\Requests\TagTeams\StoreRequest;
use App\Http\Requests\TagTeams\UpdateRequest;
use App\Models\TagTeam;
use App\Repositories\WrestlerRepository;

class TagTeamsController extends Controller
{
    /**
     * View a list of tag teams.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        $this->authorize('viewList', TagTeam::class);

        return view('tagteams.index');
    }

    /**
     * Show the form for creating a new tag team.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        $this->authorize('create', TagTeam::class);

        return view('tagteams.create', [
            'wrestlers' => WrestlerRepository::getAvailableWrestlersForNewTagTeam()->pluck('name', 'id'),
        ]);
    }

    /**
     * Create a new tag team.
     *
     * @param  \App\Http\Requests\TagTeams\StoreRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
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
    public function show(TagTeam $tagTeam): View
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
    public function edit(TagTeam $tagTeam): View
    {
        $this->authorize('update', $tagTeam);

        return view('tagteams.edit', [
            'tagTeam' => $tagTeam,
            'wrestlers' => WrestlerRepository::getAvailableWrestlersForExistingTagTeam($tagTeam)->pluck('name', 'id'),
        ]);
    }

    /**
     * Update a given tag team.
     *
     * @param  \App\Http\Requests\TagTeams\UpdateRequest  $request
     * @param  \App\Models\TagTeam  $tagTeam
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateRequest $request, TagTeam $tagTeam): RedirectResponse
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
    public function destroy(TagTeam $tagTeam): RedirectResponse
    {
        $this->authorize('delete', $tagTeam);

        DeleteAction::run($tagTeam);

        return to_route('tag-teams.index');
    }
}
