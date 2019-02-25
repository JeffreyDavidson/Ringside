<?php

namespace App\Http\Controllers;

use App\Manager;

class ManagerInjuriesController extends Controller
{
    /**
     * Create an injury for the manager.
     *
     * @param  \App\Manager  $manager
     * @return \lluminate\Http\RedirectResponse
     */
    public function store(Manager $manager)
    {
        $this->authorize('injure', $manager);

        $manager->injure();

        return redirect()->route('managers.index', ['state' => 'injured']);
    }

    /**
     * Recover an injured manager.
     *
     * @param  \App\Manager  $manager
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(Manager $manager)
    {
        $this->authorize('recover', $manager);

        $manager->recover();

        return redirect()->route('managers.index');
    }
}
