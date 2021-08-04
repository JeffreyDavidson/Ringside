<?php

namespace App\Http\Controllers\Wrestlers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\ClearInjuryRequest;
use App\Models\Wrestler;
use App\Services\WrestlerService;

class ClearInjuryController extends Controller
{
    /**
     * Have a wrestler recover from an injury.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \App\Http\Requests\Wrestlers\ClearInjuryRequest  $request
     * @param  \App\Services\WrestlerService $wrestlerService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, ClearInjuryRequest $request, WrestlerService $wrestlerService)
    {
        $wrestlerService->clearFromInjury($wrestler);

        return redirect()->route('wrestlers.index');
    }
}
