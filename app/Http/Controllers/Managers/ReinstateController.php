<?php

namespace App\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\ReinstateRequest;
use App\Models\Manager;
use App\Services\ManagerService;

class ReinstateController extends Controller
{
    /**
     * Reinstate a suspended manager.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \App\Http\Requests\Managers\ReinstateRequest  $request
     * @param  \App\Services\ManagerService $managerService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager, ReinstateRequest $request, ManagerService $managerService)
    {
        $managerService->reinstate($manager);

        return redirect()->route('managers.index');
    }
}
