<?php

namespace App\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\SuspendRequest;
use App\Models\Manager;
use App\Services\ManagerService;

class SuspendController extends Controller
{
    /**
     * Suspend a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \App\Http\Requests\Managers\SuspendRequest  $request
     * @param  \App\Services\ManagerService $managerService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager, SuspendRequest $request, ManagerService $managerService)
    {
        $managerService->suspend($manager);

        return redirect()->route('managers.index');
    }
}
