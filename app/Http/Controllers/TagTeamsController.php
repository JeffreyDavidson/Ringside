<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TagTeams\TagTeam;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Tests\Feature\Http\Controllers\TagTeamsControllerTest;

/**
 * Controller for managing tag teams.
 *
 * @see TagTeamsControllerTest
 */
class TagTeamsController
{
    /**
     * View a list of tag teams.
     *
     * @see TagTeamsControllerTest::test_index_returns_a_view()
     */
    public function index(): View
    {
        Gate::authorize('viewList', TagTeam::class);

        return view('tag-teams.index');
    }

    /**
     * Show the profile of a tag team.
     *
     * @see TagTeamsControllerTest::test_show_returns_a_view()
     */
    public function show(TagTeam $tagTeam): View
    {
        Gate::authorize('view', $tagTeam);

        return view('tag-teams.show', [
            'tagTeam' => $tagTeam,
        ]);
    }
}
