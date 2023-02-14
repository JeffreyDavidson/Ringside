<?php

declare(strict_types=1);

namespace App\Http\Controllers\Titles;

use App\Actions\Titles\UnretireAction;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Controller;
use App\Models\Title;
use Illuminate\Http\RedirectResponse;

class UnretireController extends Controller
{
    /**
     * Unretires a title.
     */
    public function __invoke(Title $title): RedirectResponse
    {
        $this->authorize('unretire', $title);

        try {
            UnretireAction::run($title);
        } catch (CannotBeUnretiredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return to_route('titles.index');
    }
}
