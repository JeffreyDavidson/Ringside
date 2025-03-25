<?php

declare(strict_types=1);

namespace App\Http\Controllers\TagTeams;

use App\Actions\TagTeams\ReinstateAction;
use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Controller;
use App\Models\TagTeam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ReinstateController extends Controller
{
    /**
     * Reinstate a tag team.
     */
    public function __invoke(TagTeam $tagTeam): RedirectResponse
    {
        Gate::authorize('reinstate', $tagTeam);

        try {
            ReinstateAction::run($tagTeam);
        } catch (CannotBeReinstatedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('tag-teams.index');
    }
}
