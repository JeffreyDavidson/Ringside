<?php

namespace App\Http\Livewire\Referees;

use App\Http\Livewire\BaseComponent;
use App\Models\Referee;

class EmployedReferees extends BaseComponent
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $employedReferees = Referee::query()
            ->employed()
            ->withFirstEmployedAtDate()
            ->orderByFirstEmployedAtDate()
            ->orderBy('last_name')
            ->paginate($this->perPage);

        return view('livewire.referees.employed-referees', [
            'employedReferees' => $employedReferees,
        ]);
    }
}
