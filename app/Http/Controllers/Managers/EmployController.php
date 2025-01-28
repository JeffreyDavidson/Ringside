<?php

declare(strict_types=1);

namespace App\Http\Controllers\Managers;

use App\Actions\Managers\EmployAction;
use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Controller;
use App\Models\Manager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class EmployController extends Controller
{
    /**
     * Employ a manager.
     */
    public function __invoke(Manager $manager): RedirectResponse
    {
        Gate::authorize('employ', $manager);

        try {
            app(EmployAction::class)->handle($manager);
        } catch (CannotBeEmployedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('managers.index');
    }
}
