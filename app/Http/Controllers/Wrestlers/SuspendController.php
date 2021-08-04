<?php

namespace App\Http\Controllers\Wrestlers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\SuspendRequest;
use App\Models\Wrestler;
use App\Services\WrestlerService;

class SuspendController extends Controller
{
    /**
     * Suspend a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \App\Http\Requests\Wrestlers\SuspendRequest  $request
     * @param  \App\Services\WrestlerService  $wrestlerService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, SuspendRequest $request, WrestlerService $wrestlerService)
    {
        $wrestlerService->suspend($wrestler);

        return redirect()->route('wrestlers.index');
    }
}
