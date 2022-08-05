<?php

declare(strict_types=1);

namespace App\Http\Controllers\Referees;

use App\Actions\Referees\RestoreAction;
use App\Http\Controllers\Controller;
use App\Models\Referee;

class RestoreController extends Controller
{
    /**
     * Restore a deleted referee.
     *
     * @param  int  $refereeId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(int $refereeId)
    {
        $referee = Referee::onlyTrashed()->findOrFail($refereeId);

        $this->authorize('restore', Referee::class);

        RestoreAction::run($referee);

        return to_route('referees.index');
    }
}
