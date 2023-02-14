<?php

declare(strict_types=1);

namespace App\Http\Controllers\Referees;

use App\Actions\Referees\SuspendAction;
use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\Controller;
use App\Models\Referee;
use Illuminate\Http\RedirectResponse;

class SuspendController extends Controller
{
    /**
     * Suspend a referee.
     */
    public function __invoke(Referee $referee): RedirectResponse
    {
        $this->authorize('suspend', $referee);

        try {
            SuspendAction::run($referee);
        } catch (CannotBeSuspendedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('referees.index');
    }
}
