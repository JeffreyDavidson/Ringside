<?php

declare(strict_types=1);

namespace App\Http\Controllers\Wrestlers;

use App\Actions\Wrestlers\ReinstateAction;
use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Controller;
use App\Models\Wrestler;
use Illuminate\Http\RedirectResponse;

class ReinstateController extends Controller
{
    /**
     * Reinstate a wrestler.
     */
    public function __invoke(Wrestler $wrestler): RedirectResponse
    {
        $this->authorize('reinstate', $wrestler);

        try {
            ReinstateAction::run($wrestler);
        } catch (CannotBeReinstatedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('wrestlers.index');
    }
}
