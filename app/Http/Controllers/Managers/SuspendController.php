<?php

declare(strict_types=1);

namespace App\Http\Controllers\Managers;

use App\Actions\Managers\SuspendAction;
use App\Exceptions\CannotBeSuspendedException;
use App\Models\Manager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class SuspendController
{
    /**
     * Suspend a manager.
     */
    public function __invoke(Manager $manager): RedirectResponse
    {
        Gate::authorize('suspend', $manager);

        try {
            resolve(SuspendAction::class)->handle($manager);
        } catch (CannotBeSuspendedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('managers.index');
    }
}
