<?php

namespace App\Http\Controllers\Wrestlers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\ReleaseRequest;
use App\Models\Wrestler;
use App\Services\WrestlerService;

class ReleaseController extends Controller
{
    /**
     * Release a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \App\Http\Requests\Wrestlers\ReleaseRequest  $request
     * @param  \App\Services\WrestlerService $wrestlerService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, ReleaseRequest $request, WrestlerService $wrestlerService)
    {
        $wrestlerService->release($wrestler);

        return redirect()->route('wrestlers.index');
    }
}
