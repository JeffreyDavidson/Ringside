<?php

declare(strict_types=1);

namespace App\Http\Controllers\TagTeams;

use App\Http\Controllers\Controller;
use App\Models\TagTeam;
use Illuminate\Contracts\View\View;
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
