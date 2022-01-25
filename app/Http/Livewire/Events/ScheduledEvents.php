<?php

namespace App\Http\Livewire\Events;

use App\Http\Livewire\BaseComponent;
use App\Models\Event;

class ScheduledEvents extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $scheduledEvents = Event::query()
            ->scheduled()
            ->paginate($this->perPage);

        return view('livewire.events.scheduled-events', [
            'scheduledEvents' => $scheduledEvents,
        ]);
    }
}
