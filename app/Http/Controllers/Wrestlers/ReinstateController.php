<?php

namespace App\Http\Controllers\Wrestlers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\ReinstateRequest;
use App\Models\Wrestler;

class ReinstateController extends Controller
{
    /**
     * Reinstate a wrestler.
     *
     * @param  App\Models\Wrestler  $wrestler
     * @param  App\Http\Requests\Wrestlers\ReinstateRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, ReinstateRequest $request)
    {
        $wrestler->reinstate();

        return redirect()->route('wrestlers.index');
    }
}
