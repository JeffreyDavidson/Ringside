<?php

namespace App\Http\Livewire\Events;

use App\Models\Event;
use Livewire\Component;
use Livewire\WithPagination;

class UnscheduledEvents extends Component
{
    use WithPagination;

    public $perPage = 10;

    public function paginationView()
    {
        return 'pagination.datatables';
    }

    public function render()
    {
        return view('livewire.events.unscheduled-events', [
            'unscheduledEvents' => Event::unscheduled()->paginate($this->perPage),
        ]);
    }
}
