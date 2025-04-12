<?php

declare(strict_types=1);

namespace App\Http\Controllers\Managers;

use App\Actions\Managers\ReinstateAction;
use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Controller;
use App\Models\Manager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class ReinstateController extends Controller
{
    /**
     * Reinstate a suspended manager.
     */
    public function __invoke(Manager $manager): RedirectResponse
    {
        Gate::authorize('reinstate', $manager);

        try {
            ReinstateAction::run($manager);
        } catch (CannotBeReinstatedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('managers.index');
    }
}
