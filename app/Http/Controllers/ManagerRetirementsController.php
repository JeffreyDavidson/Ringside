<?php

namespace App\Http\Controllers;

use App\Manager;

class ManagerRetirementsController extends Controller
{
    /**
     * Retire a manager.
     *
     * @param  \App\Manager  $manager
     * @return \lluminate\Http\RedirectResponse
     */
    public function store(Manager $manager)
    {
        $this->authorize('retire', $manager);

        $manager->retire();

        return redirect()->route('managers.index', ['state' => 'retired']);
    }

    /**
     * Unretire a retired manager.
     *
     * @param  \App\Manager  $manager
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(Manager $manager)
    {
        $this->authorize('unretire', $manager);

        $manager->unretire();

        return redirect()->route('managers.index');
    }
}
