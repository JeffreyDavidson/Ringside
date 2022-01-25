<?php

namespace App\Http\Controllers\Managers;

use App\Actions\Managers\ReleaseAction;
use App\Exceptions\CannotBeReleasedException;
use App\Http\Controllers\Controller;
use App\Models\Manager;

class ReleaseController extends Controller
{
    /**
     * Release a manager.
     *
     * @param  \App\Models\Manager  $manager
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager)
    {
        $this->authorize('release', $manager);

        throw_unless($manager->canBeReleased(), new CannotBeReleasedException);

        ReleaseAction::run($manager);

        return redirect()->route('managers.index');
    }
}
