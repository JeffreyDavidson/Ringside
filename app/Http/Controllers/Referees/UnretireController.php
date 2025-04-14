<?php

declare(strict_types=1);

namespace App\Http\Controllers\Referees;

use App\Actions\Referees\UnretireAction;
use App\Exceptions\CannotBeUnretiredException;
use App\Models\Referee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class UnretireController
{
    /**
     * Unretire a referee.
     */
    public function __invoke(Referee $referee): RedirectResponse
    {
        Gate::authorize('unretire', $referee);

        try {
            resolve(UnretireAction::class)->handle($referee);
        } catch (CannotBeUnretiredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('referees.index');
    }
}
