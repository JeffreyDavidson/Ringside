<?php

declare(strict_types=1);

namespace App\Http\Controllers\Referees;

use App\Actions\Referees\RetireAction;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Controller;
use App\Models\Referee;
use Illuminate\Http\RedirectResponse;

class RetireController extends Controller
{
    /**
     * Retire a referee.
     */
    public function __invoke(Referee $referee): RedirectResponse
    {
        $this->authorize('retire', $referee);

        try {
            RetireAction::run($referee);
        } catch (CannotBeRetiredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('referees.index');
    }
}
