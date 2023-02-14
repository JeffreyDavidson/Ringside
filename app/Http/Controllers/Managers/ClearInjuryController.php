<?php

declare(strict_types=1);

namespace App\Http\Controllers\Managers;

use App\Actions\Managers\ClearInjuryAction;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Http\Controllers\Controller;
use App\Models\Manager;
use Illuminate\Http\RedirectResponse;

class ClearInjuryController extends Controller
{
    /**
     * Clear a manager.
     */
    public function __invoke(Manager $manager): RedirectResponse
    {
        $this->authorize('clearFromInjury', $manager);

        try {
            ClearInjuryAction::run($manager);
        } catch (CannotBeClearedFromInjuryException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('managers.index');
    }
}
