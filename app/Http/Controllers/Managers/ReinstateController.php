<?php

namespace App\Http\Controllers\Managers;

use App\Actions\Managers\ReinstateAction;
use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Controller;
use App\Models\Manager;

class ReinstateController extends Controller
{
    /**
     * Reinstate a suspended manager.
     *
     * @param  \App\Models\Manager  $manager
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager)
    {
        $this->authorize('reinstate', $manager);

        throw_unless($manager->canBeReinstated(), new CannotBeReinstatedException);

        ReinstateAction::run($manager);

        return redirect()->route('managers.index');
    }
}
