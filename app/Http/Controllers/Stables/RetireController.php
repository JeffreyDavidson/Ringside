<?php

namespace App\Http\Controllers\Stables;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stables\RetireRequest;
use App\Models\Stable;
use App\Services\StableService;

class RetireController extends Controller
{
    /**
     * Retire a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \App\Http\Requests\Stables\RetireRequest $request
     * @param  \App\Services\StableService $stableService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Stable $stable, RetireRequest $request, StableService $stableService)
    {
        $stableService->retire($stable);

        return redirect()->route('stables.index');
    }
}
