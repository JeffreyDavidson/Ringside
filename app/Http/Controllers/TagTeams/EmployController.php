<?php

declare(strict_types=1);

namespace App\Http\Controllers\TagTeams;

use App\Actions\TagTeams\EmployAction;
use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Controller;
use App\Models\TagTeam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class EmployController extends Controller
{
    /**
     * Employ a tag team.
     */
    public function __invoke(TagTeam $tagTeam): RedirectResponse
    {
        Gate::authorize('employ', $tagTeam);

        try {
            EmployAction::run($tagTeam);
        } catch (CannotBeEmployedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('tag-teams.index');
    }
}
