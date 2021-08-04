<?php

namespace App\Http\Controllers\Stables;

use App\Models\Stable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stables\UnretireRequest;
use App\Services\StableService;

class UnretireController extends Controller
{
    /**
     * Unretire a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \App\Http\Requests\Stables\UnretireRequest  $request
     * @param  \App\Services\StableService $stableService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Stable $stable, UnretireRequest $request, StableService $stableService)
    {
        $stableService->unretire($stable);

        return redirect()->route('stables.index');
    }
}
