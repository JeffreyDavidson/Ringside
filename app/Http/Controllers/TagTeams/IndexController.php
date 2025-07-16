<?php

declare(strict_types=1);

namespace App\Http\Controllers\TagTeams;

use App\Models\TagTeams\TagTeam;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class IndexController
{
    public function __invoke(): View
    {
        Gate::authorize('viewList', TagTeam::class);

        return view('tag-teams.index');
    }
}
