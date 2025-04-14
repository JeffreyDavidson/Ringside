<?php

declare(strict_types=1);

namespace App\Http\Controllers\TagTeams;

use App\Actions\TagTeams\SuspendAction;
use App\Exceptions\CannotBeSuspendedException;
use App\Models\TagTeam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class SuspendController
{
    /**
     * Suspend a tag team.
     */
    public function __invoke(TagTeam $tagTeam): RedirectResponse
    {
        Gate::authorize('suspend', $tagTeam);

        try {
            resolve(SuspendAction::class)->handle($tagTeam);
        } catch (CannotBeSuspendedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('tag-teams.index');
    }
}
