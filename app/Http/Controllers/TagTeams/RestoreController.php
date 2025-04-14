<?php

declare(strict_types=1);

namespace App\Http\Controllers\TagTeams;

use App\Actions\TagTeams\RestoreAction;
use App\Models\TagTeam;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class RestoreController
{
    /**
     * Restore a deleted tag team.
     */
    public function __invoke(int $tagTeamId): RedirectResponse
    {
        $tagTeam = TagTeam::onlyTrashed()->findOrFail($tagTeamId);

        Gate::authorize('restore', $tagTeam);

        try {
            resolve(RestoreAction::class)->handle($tagTeam);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('tag-teams.index');
    }
}
