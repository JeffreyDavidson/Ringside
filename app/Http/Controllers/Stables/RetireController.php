<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stables;

use App\Actions\Stables\RetireAction;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Controller;
use App\Models\Stable;

class RetireController extends Controller
{
    /**
     * Retire a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Stable $stable)
    {
        $this->authorize('retire', $stable);

        throw_unless($stable->canBeRetired(), CannotBeRetiredException::class);

        RetireAction::run($stable);

        return to_route('stables.index');
    }
}
