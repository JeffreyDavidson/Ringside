<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Titles\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Tests\Feature\Http\Controllers\TitlesControllerTest;

/**
 * Controller for managing titles.
 *
 * @see TitlesControllerTest
 */
class TitlesController
{
    /**
     * View a list of titles.
     *
     * @see TitlesControllerTest::test_index_returns_a_view()
     */
    public function index(): View
    {
        Gate::authorize('viewList', Title::class);

        return view('titles.index');
    }

    /**
     * Show the profile of a title.
     *
     * @see TitlesControllerTest::test_show_returns_a_view()
     */
    public function show(Title $title): View
    {
        Gate::authorize('view', Title::class);

        return view('titles.show', ['title' => $title]);
    }
}
