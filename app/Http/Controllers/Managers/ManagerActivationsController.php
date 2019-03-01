<?php

namespace App\Http\Controllers\Managers;

use App\Models\Manager;
use App\Http\Controllers\Controller;

class ManagerActivationsController extends Controller
{
    /**
     * Activate and inactive manager.
     *
     * @param  \App\Models\Manager  $manager
     * @return \lluminate\Http\RedirectResponse
     */
    public function store(Manager $manager)
    {
        $this->authorize('activate', $manager);

        $manager->activate();

        return redirect()->route('managers.index');
    }

    /**
     * Deactivate an active manager.
     *
     * @param  \App\Models\Manager  $manager
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(Manager $manager)
    {
        $this->authorize('deactivate', $manager);

        $manager->deactivate();

        return redirect()->route('managers.index', ['state' => 'inactive']);
    }
}
