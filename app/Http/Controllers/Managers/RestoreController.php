<?php

namespace App\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\Manager;
use App\Services\ManagerService;

class RestoreController extends Controller
{
    /**
     * Restore a manager.
     *
     * @param  int  $managerId
     * @param  \App\Services\ManagerService $managerService
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(int $managerId, ManagerService $managerService)
    {
        $manager = Manager::onlyTrashed()->findOrFail($managerId);

        $this->authorize('restore', $manager);

        $managerService->restore($manager);

        return redirect()->route('managers.index');
    }
}
