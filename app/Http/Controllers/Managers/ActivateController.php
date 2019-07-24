<?php

namespace App\Http\Controllers\Managers;

use App\Models\Manager;
use App\Http\Controllers\Controller;

class ActivateController extends Controller
{
    /**
     * Activate a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager)
    {
        $this->authorize('activate', $manager);

        $manager->activate();

        return redirect()->route('managers.index');
    }
}
