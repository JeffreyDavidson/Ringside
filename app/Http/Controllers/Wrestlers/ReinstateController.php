<?php

namespace App\Http\Controllers\Wrestlers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\ReinstateRequest;
use App\Models\Wrestler;
use App\Services\WrestlerService;

class ReinstateController extends Controller
{
    /**
     * Reinstate a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \App\Http\Requests\Wrestlers\ReinstateRequest  $request
     * @param  \App\Services\WrestlerService  $wrestlerService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, ReinstateRequest $request, WrestlerService $wrestlerService)
    {
        $wrestlerService->reinstate($wrestler);

        return redirect()->route('wrestlers.index');
    }
}
