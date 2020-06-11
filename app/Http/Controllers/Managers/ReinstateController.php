<?php

namespace App\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\ReinstateRequest;
use App\Models\Manager;

class ReinstateController extends Controller
{
    /**
     * Reinstate a manager.
     *
     * @param  App\Models\Manager  $manager
     * @param  App\Http\Requests\Managers\ReinstateRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager, ReinstateRequest $request)
    {
        $manager->reinstate();

        return redirect()->route('managers.index');
    }
}
