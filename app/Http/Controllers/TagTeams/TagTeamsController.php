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
use App\Repositories\WrestlerRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TagTeamsController extends Controller
{
    /**
     * View a list of tag teams.
     */
    public function index(): View
    {
        Gate::authorize('viewList', TagTeam::class);

        return view('tag-teams.index');
    }

    /**
     * Show the form for creating a new tag team.
     */
    public function create(TagTeam $tagTeam): View
    {
        Gate::authorize('create', TagTeam::class);

        return view('tag-teams.create', [
            'tagTeam' => $tagTeam,
            'wrestlers' => WrestlerRepository::getAvailableWrestlersForNewTagTeam()->pluck('name', 'id'),
        ]);
    }

    /**
     * Create a new tag team.
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        CreateAction::run(TagTeamData::fromStoreRequest($request));

        return to_route('tag-teams.index');
    }

    /**
     * Show the profile of a tag team.
     */
    public function show(TagTeam $tagTeam): View
    {
        Gate::authorize('view', $tagTeam);

        return view('tag-teams.show', [
            'tagTeam' => $tagTeam,
        ]);
    }

    /**
     * Show the form for editing a tag team.
     */
    public function edit(TagTeam $tagTeam): View
    {
        Gate::authorize('update', $tagTeam);

        return view('tag-teams.edit', [
            'tagTeam' => $tagTeam,
            'wrestlers' => WrestlerRepository::getAvailableWrestlersForExistingTagTeam($tagTeam)->pluck('name', 'id'),
        ]);
    }

    /**
     * Update a given tag team.
     */
    public function update(UpdateRequest $request, TagTeam $tagTeam): RedirectResponse
    {
        UpdateAction::run($tagTeam, TagTeamData::fromUpdateRequest($request));

        return to_route('tag-teams.index');
    }

    /**
     * Delete a tag team.
     */
    public function destroy(TagTeam $tagTeam): RedirectResponse
    {
        Gate::authorize('delete', $tagTeam);

        DeleteAction::run($tagTeam);

        return to_route('tag-teams.index');
    }
}
