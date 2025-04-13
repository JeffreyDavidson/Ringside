<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stables;

use App\Actions\Stables\DeactivateAction;
use App\Exceptions\CannotBeDeactivatedException;
use App\Http\Controllers\Controller;
use App\Models\Stable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class DeactivateController extends Controller
{
    /**
     * Deactivate a stable.
     */
    public function __invoke(Stable $stable): RedirectResponse
    {
        Gate::authorize('deactivate', $stable);

        try {
            resolve(DeactivateAction::class)->handle($stable);
        } catch (CannotBeDeactivatedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('stables.index');
    }
}
