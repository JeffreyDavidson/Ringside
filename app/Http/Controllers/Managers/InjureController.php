<?php

namespace App\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\InjureRequest;
use App\Models\Manager;

class InjureController extends Controller
{
    /**
     * Injure a manager.
     *
     * @param  App\Models\Manager  $manager
     * @param  App\Http\Requests\Managers\InjureRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager, InjureRequest $request)
    {
        $manager->injure();

        return redirect()->route('managers.index');
    }
}
