<?php

namespace App\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\ClearInjuryRequest;
use App\Models\Manager;
use App\Services\ManagerService;

class ClearInjuryController extends Controller
{
    /**
     * Clear a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \App\Http\Requests\Managers\ClearInjuryRequest  $request
     * @param  \App\Services\ManagerService $managerService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager, ClearInjuryRequest $request, ManagerService $managerService)
    {
        $managerService->clearFromInjury($manager);

        return redirect()->route('managers.index');
    }
}
