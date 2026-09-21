<?php

declare(strict_types=1);

namespace App\Http\Controllers\Managers;

use App\Models\Roster\Managers\Manager;
use Illuminate\Contracts\View\View;

class ManagersController
{
    public function index(): View
    {
        return view('managers.index');
    }

    public function show(Manager $manager): View
    {
        return view('managers.show', [
            'manager' => $manager->load([
                'currentTagTeams',
                'currentWrestlers',
                'firstEmployment',
            ]),
        ]);
    }
}
