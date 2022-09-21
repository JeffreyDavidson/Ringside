<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stables;

use App\Actions\Stables\RestoreAction;
use App\Http\Controllers\Controller;
use App\Models\Stable;

class RestoreController extends Controller
{
    /**
     * Restore a stable.
     *
     * @param  int  $stableId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(int $stableId)
    {
        $stable = Stable::onlyTrashed()->findOrFail($stableId);

        $this->authorize('restore', $stable);

        try {
            RestoreAction::run($stable);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('stables.index');
    }
}
