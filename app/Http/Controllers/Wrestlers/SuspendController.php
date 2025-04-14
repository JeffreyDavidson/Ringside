<?php

declare(strict_types=1);

namespace App\Http\Controllers\Wrestlers;

use App\Actions\Wrestlers\SuspendAction;
use App\Exceptions\CannotBeSuspendedException;
use App\Models\Wrestler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class SuspendController
{
    /**
     * Suspend a wrestler.
     */
    public function __invoke(Wrestler $wrestler): RedirectResponse
    {
        Gate::authorize('suspend', $wrestler);

        try {
            resolve(SuspendAction::class)->handle($wrestler);
        } catch (CannotBeSuspendedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('wrestlers.index');
    }
}
