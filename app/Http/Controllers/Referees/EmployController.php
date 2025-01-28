<?php

declare(strict_types=1);

namespace App\Http\Controllers\Referees;

use App\Actions\Referees\EmployAction;
use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Controller;
use App\Models\Referee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class EmployController extends Controller
{
    /**
     * Employ a referee.
     */
    public function __invoke(Referee $referee): RedirectResponse
    {
        Gate::authorize('employ', $referee);

        try {
            app(EmployAction::class)->handle($referee);
        } catch (CannotBeEmployedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('referees.index');
    }
}
