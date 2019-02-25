<?php

namespace App\Http\Controllers;

use App\Manager;

class ManagerSuspensionsController extends Controller
{
    /**
     * Suspend a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @return \lluminate\Http\RedirectResponse
     */
    public function store(Manager $manager)
    {
        $this->authorize('suspend', $manager);

        $manager->suspend();

        return redirect()->route('managers.index', ['state' => 'suspended']);
    }

    /**
     * Reinstate a suspended manager.
     *
     * @param  \App\Models\Manager  $manager
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(Manager $manager)
    {
        $this->authorize('reinstate', $manager);

        $manager->reinstate();

        return redirect()->route('managers.index');
    }
}
