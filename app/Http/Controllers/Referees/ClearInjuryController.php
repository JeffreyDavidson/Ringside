<?php

declare(strict_types=1);

namespace App\Http\Controllers\Referees;

use App\Actions\Referees\ClearInjuryAction;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Http\Controllers\Controller;
use App\Models\Referee;
use Illuminate\Http\RedirectResponse;

class ClearInjuryController extends Controller
{
    /**
     * Clear a referee.
     */
    public function __invoke(Referee $referee): RedirectResponse
    {
        $this->authorize('clearFromInjury', $referee);

        try {
            ClearInjuryAction::run($referee);
        } catch (CannotBeClearedFromInjuryException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('referees.index');
    }
}
