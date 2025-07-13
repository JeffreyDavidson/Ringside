<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Wrestlers\Wrestler;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Tests\Feature\Http\Controllers\WrestlersControllerTest;

/**
 * Controller for managing wrestlers.
 *
 * @see WrestlersControllerTest
 */
class WrestlersController
{
    /**
     * View a list of wrestlers.
     *
     * @see WrestlersControllerTest::test_index_returns_a_view()
     */
    public function index(): View
    {
        Gate::authorize('viewList', Wrestler::class);

        return view('wrestlers.index');
    }

    /**
     * Show the wrestler profile.
     *
     * @see WrestlersControllerTest::test_show_returns_a_view()
     */
    public function show(Wrestler $wrestler): View
    {
        Gate::authorize('view', $wrestler);

        return view('wrestlers.show', [
            'wrestler' => $wrestler,
        ]);
    }
}
