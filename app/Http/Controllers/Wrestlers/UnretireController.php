<?php

namespace App\Http\Controllers\Wrestlers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\UnretireRequest;
use App\Models\Wrestler;

class UnretireController extends Controller
{
    /**
     * Unretire a wrestler.
     *
     * @param  App\Models\Wrestler  $wrestler
     * @param  App\Http\Requests\Wrestlers\UnretireRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Wrestler $wrestler, UnretireRequest $request)
    {
        $wrestler->unretire();

        return redirect()->route('wrestlers.index');
    }
}
