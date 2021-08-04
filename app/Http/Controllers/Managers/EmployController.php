<?php

namespace App\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\EmployRequest;
use App\Models\Manager;
use App\Services\ManagerService;

class EmployController extends Controller
{
    /**
     * Employ a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \App\Http\Requests\Managers\EmployRequest  $request
     * @param  \App\Services\ManagerService $managerService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager, EmployRequest $request, ManagerService $managerService)
    {
        $managerService->employ($manager);

        return redirect()->route('managers.index');
    }
}
