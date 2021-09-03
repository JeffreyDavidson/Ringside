<?php

namespace App\Http\Controllers\Managers;

use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\EmployRequest;
use App\Models\Manager;
use App\Repositories\ManagerRepository;

class EmployController extends Controller
{
    /**
     * Employ a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \App\Http\Requests\Managers\EmployRequest  $request
     * @param  \App\Repositories\ManagerRepository  $managerRepository
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager, EmployRequest $request, ManagerRepository $managerRepository)
    {
        throw_unless($manager->canBeEmployed(), new CannotBeEmployedException);

        $employmentDate = now()->toDateTimeString();

        $managerRepository->employ($manager, $employmentDate);
        $manager->updateStatus()->save();

        return redirect()->route('managers.index');
    }
}
