<?php

declare(strict_types=1);

namespace App\Http\Controllers\Managers;

use App\Actions\Managers\ClearInjuryAction;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Models\Manager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class ClearInjuryController
{
    /**
     * Clear an injured manager.
     */
    public function __invoke(Manager $manager): RedirectResponse
    {
        Gate::authorize('clearFromInjury', $manager);

        try {
            resolve(ClearInjuryAction::class)->handle($manager);
        } catch (CannotBeClearedFromInjuryException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('managers.index');
    }
}
