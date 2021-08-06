<?php

namespace App\Http\Livewire\Referees;

use App\Models\Referee;
use Livewire\Component;
use Livewire\WithPagination;

class RetiredReferees extends Component
{
    use WithPagination;

    public $perPage = 10;

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
