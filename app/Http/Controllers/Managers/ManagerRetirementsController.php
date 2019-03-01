<?php

namespace App\Http\Controllers\Managers;

use App\Models\Manager;
use App\Http\Controllers\Controller;

class ManagerRetirementsController extends Controller
{
    /**
     * Retire a manager.
     *
     * @param  \App\Models\Manager  $manager
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
     * @param  \App\Models\Manager  $manager
     * @return \lluminate\Http\RedirectResponse
     */
    public function destroy(Manager $manager)
    {
        $this->authorize('unretire', $manager);

        $manager->unretire();

        return redirect()->route('managers.index');
    }
}
