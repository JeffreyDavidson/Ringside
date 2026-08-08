<?php

declare(strict_types=1);

namespace App\Http\Controllers\TagTeams;

use App\Models\TagTeams\TagTeam;
use Illuminate\Contracts\View\View;

class ShowController
{
    public function __invoke(TagTeam $tagTeam): View
    {
        return view('tag-teams.show', [
            'tagTeam' => $tagTeam,
        ]);
    }
}
