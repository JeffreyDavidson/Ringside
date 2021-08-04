<?php

namespace App\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\UnretireRequest;
use App\Models\Manager;
use App\Services\ManagerService;

class UnretireController extends Controller
{
    /**
     * Unretire a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \App\Http\Requests\Managers\UnretireRequest  $request
     * @param  \App\Services\ManagerService $managerService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager, UnretireRequest $request, ManagerService $managerService)
    {
        $managerService->unretire($manager);

        return redirect()->route('managers.index');
    }
}
