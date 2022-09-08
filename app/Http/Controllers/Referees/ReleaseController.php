<?php

declare(strict_types=1);

namespace App\Http\Controllers\Referees;

use App\Actions\Referees\ReleaseAction;
use App\Http\Controllers\Controller;
use App\Models\Referee;

class ReleaseController extends Controller
{
    /**
     * Release a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Referee $referee)
    {
        $this->authorize('release', $referee);

        ReleaseAction::run($referee);

        return to_route('referees.index');
    }
}
