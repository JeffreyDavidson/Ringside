<?php

namespace App\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\EmployRequest;
use App\Models\Manager;

class EmployController extends Controller
{
    /**
     * Employ a manager.
     *
     * @param  App\Models\Manager  $manager
     * @param  App\Http\Requests\Managers\EmployRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager, EmployRequest $request)
    {
        $manager->employ();

        return redirect()->route('managers.index');
    }
}
