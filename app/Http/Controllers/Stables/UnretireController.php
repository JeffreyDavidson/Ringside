<?php

namespace App\Http\Controllers\Stables;

use App\Models\Stable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stables\UnretireRequest;

class UnretireController extends Controller
{
    /**
     * Unretire a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \App\Http\Requests\Stables\UnretireRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Stable $stable, UnretireRequest $request)
    {
        $stable->unretire();

        return redirect()->route('stables.index');
    }
}
