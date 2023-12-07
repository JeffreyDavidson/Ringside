<?php

declare(strict_types=1);

namespace App\Http\Controllers\Referees;

use App\Actions\Referees\UnretireAction;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Controller;
use App\Models\Referee;
use Illuminate\Http\RedirectResponse;

class UnretireController extends Controller
{
    /**
     * Unretire a referee.
     */
    public function __invoke(Referee $referee): RedirectResponse
    {
        $this->authorize('unretire', $referee);

        try {
            UnretireAction::run($referee);
        } catch (CannotBeUnretiredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('referees.index');
    }
}
