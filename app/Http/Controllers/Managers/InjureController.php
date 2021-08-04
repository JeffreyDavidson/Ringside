<?php

namespace App\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\InjureRequest;
use App\Models\Manager;
use App\Services\ManagerService;

class InjureController extends Controller
{
    /**
     * Injure a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \App\Http\Requests\Managers\InjureRequest  $request
     * @param  \App\Services\ManagerService $managerService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager, InjureRequest $request, ManagerService $managerService)
    {
        $managerService->injure($manager);

        return redirect()->route('managers.index');
    }
}
