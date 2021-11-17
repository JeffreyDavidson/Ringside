<?php

namespace App\Http\Controllers\Managers;

use App\Actions\Managers\ClearInjuryAction;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\ClearInjuryRequest;
use App\Models\Manager;

class ClearInjuryController extends Controller
{
    /**
     * Clear a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \App\Http\Requests\Managers\ClearInjuryRequest  $request
     * @param  \App\Actions\Managers\ClearInjuryAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager, ClearInjuryRequest $request, ClearInjuryAction $action)
    {
        throw_unless($manager->canBeClearedFromInjury(), new CannotBeClearedFromInjuryException);

        $action->handle($manager);

        return redirect()->route('managers.index');
    }
}
