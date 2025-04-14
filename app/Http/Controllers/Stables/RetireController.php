<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stables;

use App\Actions\Stables\RetireAction;
use App\Exceptions\CannotBeRetiredException;
use App\Models\Stable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class RetireController
{
    /**
     * Retire a stable.
     */
    public function __invoke(Stable $stable): RedirectResponse
    {
        Gate::authorize('retire', $stable);

        try {
            resolve(RetireAction::class)->handle($stable);
        } catch (CannotBeRetiredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('stables.index');
    }
}
