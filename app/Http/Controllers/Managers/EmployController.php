<?php

declare(strict_types=1);

namespace App\Http\Controllers\Managers;

use App\Actions\Managers\EmployAction;
use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Controller;
use App\Models\Manager;
use Illuminate\Http\RedirectResponse;

class EmployController extends Controller
{
    /**
     * Employ a manager.
     */
    public function __invoke(Manager $manager): RedirectResponse
    {
        $this->authorize('employ', $manager);

        try {
            EmployAction::run($manager);
        } catch (CannotBeEmployedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('managers.index');
    }
}
