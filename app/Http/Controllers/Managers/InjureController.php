<?php

namespace App\Http\Controllers\Managers;

use App\Actions\Managers\InjureAction;
use App\Exceptions\CannotBeInjuredException;
use App\Http\Controllers\Controller;
use App\Models\Manager;

class InjureController extends Controller
{
    /**
     * Injure a manager.
     *
     * @param  \App\Models\Manager  $manager
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager)
    {
        $this->authorize('injure', $manager);

        throw_unless($manager->canBeInjured(), new CannotBeInjuredException);

        InjureAction::run($manager);

        return redirect()->route('managers.index');
    }
}
