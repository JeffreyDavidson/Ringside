<?php

namespace App\Http\Controllers\Managers;

use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\UnretireRequest;
use App\Models\Manager;
use App\Repositories\ManagerRepository;

class UnretireController extends Controller
{
    /**
     * Unretire a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \App\Http\Requests\Managers\UnretireRequest  $request
     * @param  \App\Repositories\ManagerRepository  $managerRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager, UnretireRequest $request, ManagerRepository $managerRepository)
    {
        throw_unless($manager->canBeUnretired(), new CannotBeUnretiredException);

        $unretiredDate = now()->toDateTimeString();

        $managerRepository->unretire($manager, $unretiredDate);
        $managerRepository->employ($manager, $unretiredDate);
        $manager->updateStatus()->save();

        return redirect()->route('managers.index');
    }
}
