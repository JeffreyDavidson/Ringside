<?php

declare(strict_types=1);

namespace App\Http\Controllers\Wrestlers;

use App\Actions\Wrestlers\EmployAction;
use App\Exceptions\CannotBeEmployedException;
use App\Models\Wrestler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class EmployController
{
    /**
     * Employ a wrestler.
     */
    public function __invoke(Wrestler $wrestler): RedirectResponse
    {
        Gate::authorize('employ', $wrestler);

        try {
            resolve(EmployAction::class)->handle($wrestler);
        } catch (CannotBeEmployedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('wrestlers.index');
    }
}
