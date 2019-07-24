<?php

namespace App\Http\Controllers\Managers;

use App\Models\Manager;
use App\Http\Controllers\Controller;

class RecoverController extends Controller
{
    /**
     * Recover a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager)
    {
        $this->authorize('recover', $manager);

        $manager->recover();

        return redirect()->route('managers.index');
    }
}
