<?php

namespace App\Http\Livewire\Events;

use App\Http\Livewire\BaseComponent;
use App\Models\Event;

class UnscheduledEvents extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $unscheduledEvents = Event::query()
            ->unscheduled()
            ->paginate($this->perPage);

        return view('livewire.events.unscheduled-events', [
            'unscheduledEvents' => $unscheduledEvents,
        ]);
    }
}
