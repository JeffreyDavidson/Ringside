<?php

namespace App\Http\Controllers\Managers;

use App\Models\Manager;
use App\Http\Controllers\Controller;

class RetireController extends Controller
{
    /**
     * Retire a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @return \lluminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager)
    {
        $this->authorize('retire', $manager);

        $manager->retire();

        return redirect()->route('managers.index');
    }
}
