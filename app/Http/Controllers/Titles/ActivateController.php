<?php

declare(strict_types=1);

namespace App\Http\Controllers\Titles;

use App\Actions\Titles\ActivateAction;
use App\Exceptions\CannotBeActivatedException;
use App\Models\Title;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class ActivateController
{
    public function __invoke(Title $title): RedirectResponse
    {
        Gate::authorize('activate', $title);

        try {
            resolve(ActivateAction::class)->handle($title);
        } catch (CannotBeActivatedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('titles.index');
    }
}
