<?php

namespace App\Http\Controllers\Wrestlers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\ClearInjuryRequest;
use App\Models\Wrestler;

class ClearInjuryController extends Controller
{
    /**
     * Clear a wrestler.
     *
     * @param  App\Models\Wrestler  $wrestler
     * @param  App\Http\Requests\Wrestlers\ClearInjuryRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, ClearInjuryRequest $request)
    {
        $wrestler->clearFromInjury();

        return redirect()->route('wrestlers.index');
    }
}
