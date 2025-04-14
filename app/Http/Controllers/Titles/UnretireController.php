<?php

declare(strict_types=1);

namespace App\Http\Controllers\Titles;

use App\Actions\Titles\UnretireAction;
use App\Exceptions\CannotBeUnretiredException;
use App\Models\Title;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class UnretireController
{
    /**
     * Unretire a title.
     */
    public function __invoke(Title $title): RedirectResponse
    {
        Gate::authorize('unretire', $title);

        try {
            resolve(UnretireAction::class)->handle($title);
        } catch (CannotBeUnretiredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('titles.index');
    }
}
