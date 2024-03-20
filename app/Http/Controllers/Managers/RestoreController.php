<?php

declare(strict_types=1);

namespace App\Http\Controllers\Managers;

use App\Actions\Managers\RestoreAction;
use App\Http\Controllers\Controller;
use App\Models\Manager;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class RestoreController extends Controller
{
    /**
     * Restore a deleted manager.
     */
    public function __invoke(int $managerId): RedirectResponse
    {
        $manager = Manager::onlyTrashed()->findOrFail($managerId);

        Gate::authorize('restore', $manager);

        try {
            RestoreAction::run($manager);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('managers.index');
    }
}
