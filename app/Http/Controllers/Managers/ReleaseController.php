<?php

namespace App\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\ReleaseRequest;
use App\Models\Manager;
use App\Services\ManagerService;

class ReleaseController extends Controller
{
    /**
     * Release a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \App\Http\Requests\Managers\ReleaseRequest  $request
     * @param  \App\Services\ManagerService $managerService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager, ReleaseRequest $request, ManagerService $managerService)
    {
        $managerService->release($manager);

        return redirect()->route('managers.index');
    }
}
