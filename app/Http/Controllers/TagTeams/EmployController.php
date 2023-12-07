<?php

declare(strict_types=1);

namespace App\Http\Controllers\TagTeams;

use App\Actions\TagTeams\EmployAction;
use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Controller;
use App\Models\TagTeam;
use Illuminate\Http\RedirectResponse;

class EmployController extends Controller
{
    /**
     * Employ a tag team.
     */
    public function __invoke(TagTeam $tagTeam): RedirectResponse
    {
        $this->authorize('employ', $tagTeam);

        try {
            EmployAction::run($tagTeam);
        } catch (CannotBeEmployedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('tag-teams.index');
    }
}
