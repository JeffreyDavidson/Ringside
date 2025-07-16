<?php

declare(strict_types=1);

namespace App\Http\Controllers\TagTeams;

use App\Models\TagTeams\TagTeam;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class ShowController
{
    public function __invoke(TagTeam $tagTeam): View
    {
        Gate::authorize('view', $tagTeam);

        return view('tag-teams.show', [
            'tagTeam' => $tagTeam,
        ]);
    }
}
