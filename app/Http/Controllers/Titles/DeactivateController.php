<?php

declare(strict_types=1);

namespace App\Http\Controllers\Titles;

use App\Actions\Titles\DeactivateAction;
use App\Exceptions\CannotBeDeactivatedException;
use App\Models\Title;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class DeactivateController
{
    /**
     * Deactivates a title.
     */
    public function __invoke(Title $title): RedirectResponse
    {
        Gate::authorize('deactivate', $title);

        try {
            resolve(DeactivateAction::class)->handle($title);
        } catch (CannotBeDeactivatedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('titles.index');
    }
}
