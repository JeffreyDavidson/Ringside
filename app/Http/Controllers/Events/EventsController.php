<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Models\Events\Event;
use App\Models\Storylines\Storyline;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class EventsController
{
    public function index(): View
    {
        return view('events.index');
    }

    public function show(Event $event): View
    {
        return view('events.show', [
            'event' => $event->load('venue'),
            'storylines' => Gate::allows('viewAny', Storyline::class)
                ? Storyline::query()->where('promotion_id', $event->promotion_id)
                    ->where(fn (Builder $query) => $query->whereHas('steps', fn (Builder $steps) => $steps->where('event_id', $event->id))
                        ->orWhereHas('developments', fn (Builder $developments) => $developments->where('event_id', $event->id)))
                    ->orderBy('title')->orderBy('id')->get(['id', 'title'])
                : collect(),
        ]);
    }
}
