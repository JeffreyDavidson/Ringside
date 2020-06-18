<?php

namespace App\Http\Controllers\Managers;

use App\Models\Manager;
use App\Http\Controllers\Controller;
use App\Http\Requests\managers\ReleaseRequest;

class ReleaseController extends Controller
{
    /**
     * Fire a manager.
     *
     * @param  App\Models\Manager  $manager
     * @param  App\Http\Requests\Managers\ReleaseRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager, ReleaseRequest $request)
    {
        $manager->release();

        return redirect()->route('managers.index');
    }
}
