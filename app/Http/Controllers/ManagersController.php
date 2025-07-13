<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Managers\Manager;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Tests\Feature\Http\Controllers\ManagersControllerTest;

/**
 * Controller for managing managers.
 *
 * @see ManagersControllerTest
 */
class ManagersController
{
    /**
     * View a list of managers.
     *
     * @see ManagersControllerTest::test_index_returns_a_view()
     */
    public function index(): View
    {
        Gate::authorize('viewList', Manager::class);

        return view('managers.index');
    }

    /**
     * Show the profile of a manager.
     *
     * @see ManagersControllerTest::test_show_returns_a_view()
     */
    public function show(Manager $manager): View
    {
        Gate::authorize('view', $manager);

        return view('managers.show', [
            'manager' => $manager,
        ]);
    }
}
