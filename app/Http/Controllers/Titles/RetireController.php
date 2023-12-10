<?php

declare(strict_types=1);

namespace App\Http\Controllers\Titles;

use App\Actions\Titles\RetireAction;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Controller;
use App\Models\Title;
use Illuminate\Http\RedirectResponse;

class RetireController extends Controller
{
    /**
     * Retires a title.
     */
    public function __invoke(Title $title): RedirectResponse
    {
        $this->authorize('retire', $title);

        try {
            RetireAction::run($title);
        } catch (CannotBeRetiredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('titles.index');
    }
}
