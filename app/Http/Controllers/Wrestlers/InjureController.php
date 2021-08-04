<?php

namespace App\Http\Controllers\Wrestlers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\InjureRequest;
use App\Models\Wrestler;
use App\Services\WrestlerService;

class InjureController extends Controller
{
    /**
     * Injure a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \App\Http\Requests\Wrestlers\InjureRequest  $request
     * @param  \App\Services\WrestlerService $wrestlerService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, InjureRequest $request, WrestlerService $wrestlerService)
    {
        $wrestlerService->injure($wrestler);

        return redirect()->route('wrestlers.index');
    }
}
