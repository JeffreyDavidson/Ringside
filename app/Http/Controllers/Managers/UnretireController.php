<?php

declare(strict_types=1);

namespace App\Http\Controllers\Managers;

use App\Actions\Managers\UnretireAction;
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

        UnretireAction::run($manager);

        return to_route('managers.index');
    }
}
