<?php

namespace App\Http\Controllers\Managers;

use App\Actions\Managers\UnretireAction;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Controller;
use App\Models\Manager;

class UnretireController extends Controller
{
    /**
     * Unretire a manager.
     *
     * @param  \App\Models\Manager  $manager
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager)
    {
        $this->authorize('unretire', $manager);

        throw_unless($manager->canBeUnretired(), CannotBeUnretiredException::class);

        UnretireAction::run($manager);

        return redirect()->route('managers.index');
    }
}
