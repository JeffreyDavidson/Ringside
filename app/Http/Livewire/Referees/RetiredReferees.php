<?php

namespace App\Http\Livewire\Referees;

use App\Http\Livewire\BaseComponent;
use App\Models\Referee;

class RetiredReferees extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $retiredReferees = Referee::query()
            ->retired()
            ->withCurrentRetiredAtDate()
            ->orderByCurrentRetiredAtDate()
            ->orderBy('last_name')
            ->paginate($this->perPage);

        return view('livewire.referees.retired-referees', [
            'retiredReferees' => $retiredReferees,
        ]);
    }
}
