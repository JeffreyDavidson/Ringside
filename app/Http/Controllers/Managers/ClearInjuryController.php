<?php

namespace App\Http\Controllers\Managers;

use App\Actions\Managers\ClearInjuryAction;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Http\Controllers\Controller;
use App\Models\Manager;

class ClearInjuryController extends Controller
{
    /**
     * Clear a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager)
    {
        $this->authorize('clearFromInjury', $manager);

        throw_unless($manager->canBeClearedFromInjury(), CannotBeClearedFromInjuryException::class);

        ClearInjuryAction::run($manager);

        return redirect()->route('managers.index');
    }
}
