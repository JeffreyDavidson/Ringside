<?php

namespace App\Http\Controllers\Wrestlers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\UnretireRequest;
use App\Models\Wrestler;
use App\Services\WrestlerService;

class UnretireController extends Controller
{
    /**
     * Unretire a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \App\Http\Requests\Wrestlers\UnretireRequest  $request
     * @param  \App\Services\WrestlerService  $wrestlerService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, UnretireRequest $request, WrestlerService $wrestlerService)
    {
        $wrestlerService->unretire($wrestler);

        return redirect()->route('wrestlers.index');
    }
}
