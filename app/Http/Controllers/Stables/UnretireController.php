<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stables;

use App\Actions\Stables\UnretireAction;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Controller;
use App\Models\Stable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class UnretireController extends Controller
{
    /**
     * Unretire a stable.
     */
    public function __invoke(Stable $stable): RedirectResponse
    {
        Gate::authorize('unretire', $stable);

        try {
            UnretireAction::run($stable);
        } catch (CannotBeUnretiredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('stables.index');
    }
}
