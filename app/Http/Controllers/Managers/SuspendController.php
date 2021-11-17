<?php

namespace App\Http\Controllers\Managers;

use App\Actions\Managers\SuspendAction;
use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\SuspendRequest;
use App\Models\Manager;

class SuspendController extends Controller
{
    /**
     * Suspend a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \App\Http\Requests\Managers\SuspendRequest  $request
     * @param  \App\Actions\Managers\SuspendAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager, SuspendRequest $request, SuspendAction $action)
    {
        throw_unless($manager->canBeSuspended(), new CannotBeSuspendedException);

        $action->handle($manager);

        return redirect()->route('managers.index');
    }
}
