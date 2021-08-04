<?php

namespace App\Http\Controllers\Wrestlers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\RetireRequest;
use App\Models\Wrestler;
use App\Services\WrestlerService;

class RetireController extends Controller
{
    /**
     * Retire a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \App\Http\Requests\Wrestlers\RetireRequest  $request
     * @param  \App\Services\WrestlerService $wrestlerService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, RetireRequest $request, WrestlerService $wrestlerService)
    {
        $wrestlerService->retire($wrestler);

        return redirect()->route('wrestlers.index');
    }
}
