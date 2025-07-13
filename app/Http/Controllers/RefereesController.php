<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Referees\Referee;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Tests\Feature\Http\Controllers\RefereesControllerTest;

/**
 * Controller for managing referees.
 *
 * @see RefereesControllerTest
 */
class RefereesController
{
    /**
     * View a list of referees.
     *
     * @see RefereesControllerTest::test_index_returns_a_view()
     */
    public function index(): View
    {
        Gate::authorize('viewList', Referee::class);

        return view('referees.index');
    }

    /**
     * Show the profile of a referee.
     *
     * @see RefereesControllerTest::test_show_returns_a_view()
     */
    public function show(Referee $referee): View
    {
        Gate::authorize('view', $referee);

        return view('referees.show', [
            'referee' => $referee,
        ]);
    }
}
