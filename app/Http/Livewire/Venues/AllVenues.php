<?php

namespace App\Http\Livewire\Venues;

use App\Http\Livewire\BaseComponent;
use App\Models\Venue;

class AllVenues extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $venues = Venue::query()
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.venues.all-venues', [
            'venues' => $venues,
        ]);
    }
}
