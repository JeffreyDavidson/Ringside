<?php

declare(strict_types=1);

namespace App\Http\Controllers\Referees;

use App\Actions\Referees\ClearInjuryAction;
use App\Http\Controllers\Controller;
use App\Models\Referee;

class ClearInjuryController extends Controller
{
    /**
     * Clear a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee)
    {
        $this->authorize('clearFromInjury', $referee);

        ClearInjuryAction::run($referee);

        return to_route('referees.index');
    }
}
