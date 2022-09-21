<?php

declare(strict_types=1);

namespace App\Http\Controllers\Wrestlers;

use App\Actions\Wrestlers\RestoreAction;
use App\Http\Controllers\Controller;
use App\Models\Wrestler;

class RestoreController extends Controller
{
    /**
     * Restore a deleted wrestler.
     *
     * @param  int  $wrestlerId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(int $wrestlerId)
    {
        $wrestler = Wrestler::onlyTrashed()->findOrFail($wrestlerId);

        $this->authorize('restore', $wrestler);

        try {
            RestoreAction::run($wrestler);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('wrestlers.index');
    }
}
