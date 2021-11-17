<?php

namespace App\Http\Controllers\Managers;

use App\Actions\Managers\InjureAction;
use App\Exceptions\CannotBeInjuredException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\InjureRequest;
use App\Models\Manager;

class InjureController extends Controller
{
    /**
     * Injure a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @param  \App\Http\Requests\Managers\InjureRequest  $request
     * @param  \App\Actions\Managers\InjureAction  $action
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager, InjureRequest $request, InjureAction $action)
    {
        throw_unless($manager->canBeInjured(), new CannotBeInjuredException);

        $action->handle($manager);

        return redirect()->route('managers.index');
    }
}
