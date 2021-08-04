<?php

namespace App\Http\Controllers\Stables;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stables\ActivateRequest;
use App\Models\Stable;
use App\Services\StableService;

class ActivateController extends Controller
{
    /**
     * Activate a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \App\Http\Requests\Stables\ActivateRequest  $stable
     * @param  \App\Services\StableService $stableService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Stable $stable, ActivateRequest $request, StableService $stableService)
    {
        $stableService->activate($stable);

        return redirect()->route('stables.index');
    }
}
