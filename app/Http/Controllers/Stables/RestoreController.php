<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stables;

use App\Actions\Stables\RestoreAction;
use App\Http\Controllers\Controller;
use App\Models\Stable;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class RestoreController extends Controller
{
    /**
     * Restore a stable.
     */
    public function __invoke(int $stableId): RedirectResponse
    {
        $stable = Stable::onlyTrashed()->findOrFail($stableId);

        Gate::authorize('restore', $stable);

        try {
            RestoreAction::run($stable);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('stables.index');
    }
}
