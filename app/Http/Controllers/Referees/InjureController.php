<?php

declare(strict_types=1);

namespace App\Http\Controllers\Referees;

use App\Actions\Referees\InjureAction;
use App\Exceptions\CannotBeInjuredException;
use App\Models\Referee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class InjureController
{
    /**
     * Injure a referee.
     */
    public function __invoke(Referee $referee): RedirectResponse
    {
        Gate::authorize('injure', $referee);

        try {
            resolve(InjureAction::class)->handle($referee);
        } catch (CannotBeInjuredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('referees.index');
    }
}
