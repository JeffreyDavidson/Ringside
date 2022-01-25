<?php

namespace App\Http\Livewire\Events;

use App\Http\Livewire\BaseComponent;
use App\Models\Event;

class PastEvents extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $pastEvents = Event::query()
            ->past()
            ->paginate($this->perPage);

        return view('livewire.events.past-events', [
            'pastEvents' => $pastEvents,
        ]);
    }
}
