<?php

declare(strict_types=1);

namespace App\Http\Controllers\Titles;

use App\Actions\Titles\RetireAction;
use App\Exceptions\CannotBeRetiredException;
use App\Models\Title;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class RetireController
{
    /**
     * Retires a title.
     */
    public function __invoke(Title $title): RedirectResponse
    {
        Gate::authorize('retire', $title);

        try {
            resolve(RetireAction::class)->handle($title);
        } catch (CannotBeRetiredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('titles.index');
    }
}
