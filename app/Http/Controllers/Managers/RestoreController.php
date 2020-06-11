<?php

namespace App\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\Manager;

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
