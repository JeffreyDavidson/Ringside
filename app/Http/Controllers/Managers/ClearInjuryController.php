<?php

namespace App\Http\Controllers\Managers;

use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\ClearInjuryRequest;
use App\Models\Manager;
use App\Repositories\ManagerRepository;

class ClearInjuryController extends Controller
{
    /**
     * Clear a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \App\Http\Requests\Managers\ClearInjuryRequest  $request
     * @param  \App\Repositories\ManagerRepository  $managerRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager, ClearInjuryRequest $request, ManagerRepository $managerRepository)
    {
        throw_unless($manager->canBeClearedFromInjury(), new CannotBeClearedFromInjuryException);

        $recoveryDate = now()->toDateTimeString();

        $manager = $managerRepository->clearInjury($manager, $recoveryDate);

        $manager->updateStatus()->save();

        return redirect()->route('managers.index');
    }
}
