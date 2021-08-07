<?php

namespace App\Http\Controllers\Stables;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stables\DisbandRequest;
use App\Models\Stable;
use App\Services\StableService;

class DisbandController extends Controller
{
    /**
     * Disband a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \App\Http\Requests\Stables\DisbandRequest  $request
     * @param  \App\Services\StableService $stableService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Stable $stable, DisbandRequest $request, StableService $stableService)
    {
        $stableService->disband($stable);

        return redirect()->route('stables.index');
    }
}
