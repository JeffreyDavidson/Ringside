<?php

namespace App\Http\Controllers\Wrestlers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\EmployRequest;
use App\Models\Wrestler;
use App\Services\WrestlerService;

class EmployController extends Controller
{
    /**
     * Employ a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \App\Http\Requests\Wrestlers\EmployRequest  $request
     * @param  \App\Services\WrestlerService $wrestlerService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, EmployRequest $request, WrestlerService $wrestlerService)
    {
        $wrestlerService->employ($wrestler);

        return redirect()->route('wrestlers.index');
    }
}
