<?php

declare(strict_types=1);

namespace App\Http\Controllers\Managers;

use App\Actions\Managers\RetireAction;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Controller;
use App\Models\Manager;
use Illuminate\Http\RedirectResponse;

class RetireController extends Controller
{
    /**
     * Retire a manager.
     */
    public function __invoke(Manager $manager): RedirectResponse
    {
        $this->authorize('retire', $manager);

        try {
            RetireAction::run($manager);
        } catch (CannotBeRetiredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('managers.index');
    }
}
