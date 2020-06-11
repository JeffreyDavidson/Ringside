<?php

namespace App\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Managers\ClearInjuryRequest;
use App\Models\Manager;
use Tests\TestCase;

class ClearInjuryController extends Controller
{
    /**
     * Clear a manager.
     *
     * @param  App\Models\Manager  $manager
     * @param  App\Http\Requests\Managers\ClearInjuryRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Manager $manager, ClearInjuryRequest $request)
    {
        $manager->clearFromInjury();

        return redirect()->route('managers.index');
    }
}
