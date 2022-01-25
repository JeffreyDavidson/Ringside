<?php

namespace App\Http\Livewire\Wrestlers;

use App\Http\Livewire\BaseComponent;
use App\Models\Wrestler;

class BookableWrestlers extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $bookableWrestlers = Wrestler::query()
            ->bookable()
            ->withFirstEmployedAtDate()
            ->orderByFirstEmployedAtDate()
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.wrestlers.bookable-wrestlers', [
            'bookableWrestlers' => $bookableWrestlers,
        ]);
    }
}
