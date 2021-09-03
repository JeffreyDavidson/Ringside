<?php

namespace App\Http\Controllers\Managers;

use App\Exceptions\CannotBeInjuredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\InjureRequest;
use App\Models\Manager;
use App\Repositories\ManagerRepository;

class InjureController extends Controller
{
    /**
     * Injure a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \App\Http\Requests\Managers\InjureRequest  $request
     * @param  \App\Repositories\ManagerRepository  $managerRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager, InjureRequest $request, ManagerRepository $managerRepository)
    {
        throw_unless($manager->canBeInjured(), new CannotBeInjuredException);

        $injureDate = now()->toDateTimeString();

        $managerRepository->injure($manager, $injureDate);
        $manager->updateStatus()->save();

        return redirect()->route('managers.index');
    }
}
