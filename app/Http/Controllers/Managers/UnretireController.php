<?php

namespace App\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\UnretireRequest;
use App\Models\Manager;

class UnretireController extends Controller
{
    /**
     * Unretire a manager.
     *
     * @param  App\Models\Manager  $manager
     * @param  App\Http\Requests\Managers\UnretireRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager, UnretireRequest $request)
    {
        $manager->unretire();

        return redirect()->route('managers.index');
    }
}
