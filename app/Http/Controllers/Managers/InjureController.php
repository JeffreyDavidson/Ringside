<?php

declare(strict_types=1);

namespace App\Http\Controllers\Managers;

use App\Actions\Managers\InjureAction;
use App\Exceptions\CannotBeInjuredException;
use App\Models\Manager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class InjureController
{
    /**
     * Injure a manager.
     */
    public function __invoke(Manager $manager): RedirectResponse
    {
        Gate::authorize('injure', $manager);

        try {
            resolve(InjureAction::class)->handle($manager);
        } catch (CannotBeInjuredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('managers.index');
    }
}
