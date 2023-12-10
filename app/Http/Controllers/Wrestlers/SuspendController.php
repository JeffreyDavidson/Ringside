<?php

declare(strict_types=1);

namespace App\Http\Controllers\Wrestlers;

use App\Actions\Wrestlers\SuspendAction;
use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\Controller;
use App\Models\Wrestler;
use Illuminate\Http\RedirectResponse;

class SuspendController extends Controller
{
    /**
     * Suspend a wrestler.
     */
    public function __invoke(Wrestler $wrestler): RedirectResponse
    {
        $this->authorize('suspend', $wrestler);

        try {
            SuspendAction::run($wrestler);
        } catch (CannotBeSuspendedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('wrestlers.index');
    }
}
