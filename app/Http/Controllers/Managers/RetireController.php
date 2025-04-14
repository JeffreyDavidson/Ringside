<?php

declare(strict_types=1);

namespace App\Http\Controllers\Managers;

use App\Actions\Managers\RetireAction;
use App\Exceptions\CannotBeRetiredException;
use App\Models\Manager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class RetireController
{
    /**
     * Retire a manager.
     */
    public function __invoke(Manager $manager): RedirectResponse
    {
        Gate::authorize('retire', $manager);

        try {
            resolve(RetireAction::class)->handle($manager);
        } catch (CannotBeRetiredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('managers.index');
    }
}
