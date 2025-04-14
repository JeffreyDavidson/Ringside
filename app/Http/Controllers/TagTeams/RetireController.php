<?php

declare(strict_types=1);

namespace App\Http\Controllers\TagTeams;

use App\Actions\TagTeams\RetireAction;
use App\Exceptions\CannotBeRetiredException;
use App\Models\TagTeam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class RetireController
{
    /**
     * Retire a tag team.
     */
    public function __invoke(TagTeam $tagTeam): RedirectResponse
    {
        Gate::authorize('retire', $tagTeam);

        try {
            resolve(RetireAction::class)->handle($tagTeam);
        } catch (CannotBeRetiredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('tag-teams.index');
    }
}
