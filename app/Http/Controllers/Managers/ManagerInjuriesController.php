<?php

namespace App\Http\Controllers\Managers;

use App\Models\Manager;
use App\Http\Controllers\Controller;

class ManagerInjuriesController extends Controller
{
    /**
     * Create an injury for the manager.
     *
     * @param  \App\Models\Manager  $manager
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
     * @param  \App\Models\Manager  $manager
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(Manager $manager)
    {
        $this->authorize('recover', $manager);

        $manager->recover();

        return redirect()->route('managers.index');
    }
}
