<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stables;

use App\Http\Controllers\Controller;
use App\Models\Stable;
use App\Services\StableService;

class RestoreController extends Controller
{
    /**
     * Restore a stable.
     *
     * @param  int  $stableId
     * @param  \App\Services\StableService $stableService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(int $stableId, StableService $stableService)
    {
        $stable = Stable::onlyTrashed()->findOrFail($stableId);

        $this->authorize('restore', $stable);

        $stableService->restore($stable);

        return to_route('stables.index');
    }
}
