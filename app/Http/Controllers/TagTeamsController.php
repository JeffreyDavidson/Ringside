<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TagTeam;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class TagTeamsController
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
     * Show the profile of a tag team.
     */
    public function show(TagTeam $tagTeam): View
    {
        Gate::authorize('view', $tagTeam);

        return view('tag-teams.show', [
            'tagTeam' => $tagTeam,
        ]);
    }
}
