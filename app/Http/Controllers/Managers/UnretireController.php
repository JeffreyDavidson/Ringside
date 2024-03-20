<?php

declare(strict_types=1);

namespace App\Http\Controllers\Managers;

use App\Actions\Managers\UnretireAction;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Controller;
use App\Models\Manager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class UnretireController extends Controller
{
    /**
     * Unretire a retired manager.
     */
    public function __invoke(Manager $manager): RedirectResponse
    {
        Gate::authorize('unretire', $manager);

        try {
            UnretireAction::run($manager);
        } catch (CannotBeUnretiredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('managers.index');
    }
}
