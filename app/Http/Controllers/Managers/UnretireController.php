<?php

namespace App\Http\Controllers\Managers;

use App\Actions\Managers\UnretireAction;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\UnretireRequest;
use App\Models\Manager;

class UnretireController extends Controller
{
    /**
     * Unretire a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \App\Http\Requests\Managers\UnretireRequest  $request
     * @param  \App\Actions\Managers\UnretireAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager, UnretireRequest $request, UnretireAction $action)
    {
        throw_unless($manager->canBeUnretired(), new CannotBeUnretiredException);

        $action->handle($manager);

        return redirect()->route('managers.index');
    }
}
