<?php

namespace App\Http\Livewire\Events;

use App\Models\Event;
use Livewire\Component;
use Livewire\WithPagination;

class PastEvents extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function render()
    {
        return view('livewire.events.past-events', [
            'pastEvents' => Event::past()->paginate($this->perPage)
        ]);
    }
}
