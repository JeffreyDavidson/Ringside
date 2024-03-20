<?php

declare(strict_types=1);

namespace App\Http\Controllers\Managers;

use App\Actions\Managers\ReleaseAction;
use App\Exceptions\CannotBeReleasedException;
use App\Http\Controllers\Controller;
use App\Models\Manager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ReleaseController extends Controller
{
    /**
     * Release a manager.
     */
    public function __invoke(Manager $manager): RedirectResponse
    {
        Gate::authorize('release', $manager);

        try {
            ReleaseAction::run($manager);
        } catch (CannotBeReleasedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('managers.index');
    }
}
