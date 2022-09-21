<?php

declare(strict_types=1);

namespace App\Http\Controllers\Managers;

use App\Actions\Managers\RestoreAction;
use App\Http\Controllers\Controller;
use App\Models\Manager;

class RestoreController extends Controller
{
    /**
     * Restore a manager.
     *
     * @param  int  $managerId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(int $managerId)
    {
        $manager = Manager::onlyTrashed()->findOrFail($managerId);

        $this->authorize('restore', $manager);

        try {
            RestoreAction::run($manager);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('managers.index');
    }
}
