<?php

namespace App\Http\Controllers\Stables;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stables\DeactivateRequest;
use App\Models\Stable;
use App\Services\StableService;

class DeactivateController extends Controller
{
    /**
     * Deactivates a stable.
     *
     * @param  \App\Models\Stable $stable
     * @param  \App\Http\Requests\Stables\DeactivateRequest $request
     * @param  \App\Services\StableService $stableService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Stable $stable, DeactivateRequest $request, StableService $stableService)
    {
        $stableService->deactivate($stable);

        return redirect()->route('stables.index');
    }
}
