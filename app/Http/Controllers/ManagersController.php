<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Manager;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class ManagersController
{
    /**
     * View a list of managers.
     */
    public function index(): View
    {
        Gate::authorize('viewList', Manager::class);

        return view('managers.index');
    }

    /**
     * Show the profile of a manager.
     */
    public function show(Manager $manager): View
    {
        Gate::authorize('view', $manager);

        return view('managers.show', [
            'manager' => $manager,
        ]);
    }
}
