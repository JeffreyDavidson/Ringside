<?php

declare(strict_types=1);

namespace App\Http\Controllers\Managers;

use Illuminate\Http\RedirectResponse;
use App\Actions\Managers\SuspendAction;
use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\Controller;
use App\Models\Manager;

class SuspendController extends Controller
{
    /**
     * Suspend a manager.
     */
    public function __invoke(Manager $manager): RedirectResponse
    {
        $this->authorize('suspend', $manager);

        try {
            SuspendAction::run($manager);
        } catch (CannotBeSuspendedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('managers.index');
    }
}
