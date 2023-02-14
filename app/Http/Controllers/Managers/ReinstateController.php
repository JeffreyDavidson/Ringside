<?php

declare(strict_types=1);

namespace App\Http\Controllers\Managers;

use Illuminate\Http\RedirectResponse;
use App\Actions\Managers\ReinstateAction;
use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Controller;
use App\Models\Manager;

class ReinstateController extends Controller
{
    /**
     * Reinstate a suspended manager.
     */
    public function __invoke(Manager $manager): RedirectResponse
    {
        $this->authorize('reinstate', $manager);

        try {
            ReinstateAction::run($manager);
        } catch (CannotBeReinstatedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('managers.index');
    }
}
