<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stables;

use App\Actions\Stables\UnretireAction;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Controller;
use App\Models\Stable;

class UnretireController extends Controller
{
    /**
     * Unretire a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Stable $stable)
    {
        $this->authorize('unretire', $stable);

        throw_unless($stable->canBeUnretired(), CannotBeUnretiredException::class);

        UnretireAction::run($stable);

        return to_route('stables.index');
    }
}
