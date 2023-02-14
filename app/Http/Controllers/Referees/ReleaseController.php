<?php

declare(strict_types=1);

namespace App\Http\Controllers\Referees;

use App\Actions\Referees\ReleaseAction;
use App\Exceptions\CannotBeReleasedException;
use App\Http\Controllers\Controller;
use App\Models\Referee;
use Illuminate\Http\RedirectResponse;

class ReleaseController extends Controller
{
    /**
     * Release a referee.
     */
    public function __invoke(Referee $referee): RedirectResponse
    {
        $this->authorize('release', $referee);

        try {
            ReleaseAction::run($referee);
        } catch (CannotBeReleasedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('referees.index');
    }
}
