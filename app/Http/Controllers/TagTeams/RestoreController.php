<?php

declare(strict_types=1);

namespace App\Http\Controllers\TagTeams;

use App\Actions\TagTeams\RestoreAction;
use App\Http\Controllers\Controller;
use App\Models\TagTeam;
use Illuminate\Http\RedirectResponse;

class RestoreController extends Controller
{
    /**
     * Restore a deleted tag team.
     */
    public function __invoke(int $tagTeamId): RedirectResponse
    {
        $tagTeam = TagTeam::onlyTrashed()->findOrFail($tagTeamId);

        $this->authorize('restore', $tagTeam);

        try {
            RestoreAction::run($tagTeam);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('tag-teams.index');
    }
}
