<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Stables\Stable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Tests\Feature\Http\Controllers\StablesControllerTest;

/**
 * Controller for managing stables.
 *
 * @see StablesControllerTest
 */
class StablesController
{
    /**
     * View a list of stables.
     *
     * @see StablesControllerTest::test_index_returns_a_view()
     */
    public function index(): View
    {
        Gate::authorize('viewList', Stable::class);

        return view('stables.index');
    }

    /**
     * Show the profile of a stable.
     *
     * @see StablesControllerTest::test_show_returns_a_view()
     */
    public function show(Stable $stable): View
    {
        Gate::authorize('view', $stable);

        return view('stables.show', [
            'stable' => $stable,
        ]);
    }
}
