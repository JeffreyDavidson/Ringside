<?php

declare(strict_types=1);

namespace App\Http\Controllers\Referees;

use App\Actions\Referees\ReleaseAction;
use App\Exceptions\CannotBeReleasedException;
use App\Models\Referee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class ReleaseController
{
    /**
     * Release a referee.
     */
    public function __invoke(Referee $referee): RedirectResponse
    {
        Gate::authorize('release', $referee);

        try {
            resolve(ReleaseAction::class)->handle($referee);
        } catch (CannotBeReleasedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('referees.index');
    }
}
