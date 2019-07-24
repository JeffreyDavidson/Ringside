<?php

namespace App\Http\Controllers\Managers;

use App\Models\Manager;
use App\Http\Controllers\Controller;

class RestoreController extends Controller
{
    /**
     * Restore a manager.
     *
     * @param  int  $managerId
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke($managerId)
    {
        $manager = Manager::onlyTrashed()->findOrFail($managerId);

        $this->authorize('restore', $manager);

        $manager->restore();

        return redirect()->route('managers.index');
    }
}
