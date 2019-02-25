<?php

namespace App\Http\Controllers;

use App\Manager;

class ManagerActivationsController extends Controller
{
    /**
     * Activate and inactive manager.
     *
     * @param  \App\Manager  $manager
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
     * @param  \App\Manager  $manager
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(Manager $manager)
    {
        $this->authorize('deactivate', $manager);

        $manager->deactivate();

        return redirect()->route('managers.index', ['state' => 'inactive']);
    }
}
