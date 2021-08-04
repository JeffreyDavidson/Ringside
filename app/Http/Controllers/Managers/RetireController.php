<?php

namespace App\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\RetireRequest;
use App\Models\Manager;
use App\Services\ManagerService;

class RetireController extends Controller
{
    /**
     * Retire a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \App\Http\Requests\Managers\RetireRequest  $request
     * @param  \App\Services\ManagerService $managerService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager, RetireRequest $request, ManagerService $managerService)
    {
        $managerService->retire($manager);

        return redirect()->route('managers.index');
    }
}
