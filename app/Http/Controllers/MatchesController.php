<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Tests\Feature\Http\Controllers\MatchesControllerTest;

/**
 * Controller for managing event matches.
 *
 * @see MatchesControllerTest
 */
class MatchesController
{
    /**
     * View a list of events matches.
     *
     * @see MatchesControllerTest::test_index_returns_a_view()
     */
    public function index(Event $event): View
    {
        Gate::authorize('viewList', EventMatch::class);

        return view('matches.index', [
            'event' => $event,
        ]);
    }
}
